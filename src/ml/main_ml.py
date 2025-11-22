#!/usr/bin/env python3
"""
main_ml.py

Improved ML script:
- accepts XLSX or CSV input (auto-detect & read)
- normalizes kecamatan names
- trains RandomForestRegressor when enough labeled rows exist
- saves/loads model (joblib)
- predicts N future years (default 10) starting from the last year in the dataset
- merges centroid lon/lat from geojson when available
- writes predictions CSV with routing + focus dates

Default input path (if not supplied) points to the uploaded file from your environment:
/var/www/html/storage/app/ml_input/uploads/dataset_2025.xlsx
(You can override with --input_csv)

Usage example:
python3 main_ml.py --input_csv /var/www/html/storage/app/ml_input/dataset.csv \
    --geojson /var/www/html/storage/app/ml_input/penelitian.geojson \
    --output_csv /var/www/html/storage/app/ml_output/predictions.csv \
    --model_path /var/www/html/storage/app/ml_models/rf_model.joblib \
    --years 10
"""
import argparse
import os
from datetime import datetime
import json

import numpy as np
import pandas as pd

# geopandas is optional (only needed if you have a geojson)
try:
    import geopandas as gpd
except Exception:
    gpd = None

from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
import joblib

# --- Config / helpers -------------------------------------------------------

DEFAULT_UPLOADED_XLSX = '/var/www/html/storage/app/ml_input/uploads/dataset_2025.xlsx'

def normalize_kecamatan(s: str) -> str:
    if pd.isna(s):
        return s
    s2 = str(s).strip().lower()
    # canonical mapping (lowercase)
    corrections = {
        'grogol petamburan': 'grogolpetamburan',
        'grogolpetamburan': 'grogolpetamburan',
        'kebon jeruk': 'kebonjeruk',
        'kebonjeruk': 'kebonjeruk',
        'pal merah': 'palmerah',
        'palmerah': 'palmerah',
        'pasar rebo': 'pasarrebo',
        'pasarrebo': 'pasarrebo',
        'taman sari': 'tamansari',
        'tamansari': 'tamansari',
        'tanah abang': 'tanahabang',
        'tanahabang': 'tanahabang',
        # add more as needed
    }
    cleaned = ''.join(ch for ch in s2 if ch.isalnum() or ch.isspace()).strip()
    return corrections.get(cleaned, cleaned.replace(' ', ''))

def generate_route_and_schedule(df: pd.DataFrame) -> pd.DataFrame:
    """
    Assigns a predicted_route, focus_month and focus_date per year group.
    Routes are simple round-robin buckets (8 per route) and months are distributed 1..12.
    """
    df = df.copy()
    # safety: ensure persentase numeric
    df['persentase'] = pd.to_numeric(df['persentase'], errors='coerce').fillna(0)
    # scoring: persentase scaled + tiny random for tie-break
    df['score'] = df['persentase'] * 100 + np.random.rand(len(df)) * 1.0
    df = df.sort_values(['tahun', 'score'], ascending=[True, False]).reset_index(drop=True)

    df['predicted_route'] = None
    df['focus_month'] = None
    df['focus_date'] = None

    for year, group in df.groupby('tahun'):
        group = group.reset_index()
        n = len(group)
        if n == 0:
            continue
        # distribute months evenly across available records (1..12)
        months = np.linspace(1, 12, max(1, n), dtype=int)
        route_num = 1
        for i, idx in enumerate(group['index']):
            month = int(months[i])
            df.at[idx, 'focus_month'] = int(month)
            df.at[idx, 'focus_date'] = f"{int(year)}-{int(month):02d}-01"
            df.at[idx, 'predicted_route'] = f"Rute-{route_num}"
            # bump route every 8 records (configurable)
            if (i + 1) % 8 == 0:
                route_num += 1

    df.drop(columns=['score'], inplace=True, errors='ignore')
    return df

# --- Main process -----------------------------------------------------------

def main(input_csv: str, geojson: str, output_csv: str, model_path: str, years: int):
    # 1) read input (CSV or XLSX)
    if input_csv is None:
        input_csv = DEFAULT_UPLOADED_XLSX

    if not os.path.exists(input_csv):
        raise FileNotFoundError(f"Input file not found: {input_csv}")

    # detect extension
    ext = os.path.splitext(input_csv)[1].lower()
    if ext in ['.xlsx', '.xls']:
        try:
            df = pd.read_excel(input_csv)
        except Exception as e:
            raise RuntimeError(f"Failed to read Excel file: {e}")
    else:
        # allow csv with various encodings and separators
        try:
            df = pd.read_csv(input_csv)
        except Exception:
            df = pd.read_csv(input_csv, encoding='utf-8', sep=',', engine='python')

    if df is None or df.shape[0] == 0:
        raise RuntimeError("Empty dataset after read.")

    # 2) normalize column names to lowercase simple keys
    df.columns = [c.strip() for c in df.columns]
    cols_lower = {c: c.strip().lower() for c in df.columns}

    # accommodate datasets with 'periode_data' column used in your example
    if 'periode_data' in [c.lower() for c in df.columns]:
        # normalize to 'tahun'
        df['tahun'] = df[[c for c in df.columns if c.lower() == 'periode_data'][0]]
    elif 'tahun' not in [c.lower() for c in df.columns]:
        # fallback: if no tahun column, set to current year
        df['tahun'] = datetime.now().year

    # normalize kecamatan column
    kec_col = next((c for c in df.columns if c.lower() == 'kecamatan'), None)
    wilayah_col = next((c for c in df.columns if c.lower() == 'wilayah'), None)
    gender_col = next((c for c in df.columns if c.lower() in ('jenis_kelamin', 'jenis kelamin', 'jk')), None)
    est_col = next((c for c in df.columns if 'jumlah_estimasi' in c.lower() or 'estimasi' in c.lower()), None)
    served_col = next((c for c in df.columns if 'mendapatkan' in c.lower() or 'pelayanan' in c.lower()), None)
    persen_col = next((c for c in df.columns if 'persentase' in c.lower()), None)

    # ensure required columns exist; create placeholders otherwise
    df['kecamatan'] = df[kec_col] if kec_col in df.columns else df['kecamatan'] if 'kecamatan' in df.columns else df.get('kecamatan', df.index.astype(str))
    df['wilayah'] = df[wilayah_col] if wilayah_col in df.columns else df.get('wilayah', '')
    df['jenis_kelamin'] = df[gender_col] if (gender_col and gender_col in df.columns) else df.get('jenis_kelamin', 'Laki-Laki')
    df['jumlah_estimasi_penderita'] = df[est_col] if (est_col and est_col in df.columns) else df.get('jumlah_estimasi_penderita', 0)
    df['jumlah_yang_mendapatkan_pelayanan_kesehatan'] = df[served_col] if (served_col and served_col in df.columns) else df.get('jumlah_yang_mendapatkan_pelayanan_kesehatan', 0)
    df['persentase'] = pd.to_numeric(df[persen_col], errors='coerce') if persen_col and persen_col in df.columns else pd.to_numeric(df.get('persentase', pd.Series([np.nan]*len(df))), errors='coerce')

    # clean kecamatan strings
    df['kecamatan'] = df['kecamatan'].astype(str).apply(normalize_kecamatan)

    # ensure tahun numeric int
    df['tahun'] = pd.to_numeric(df['tahun'], errors='coerce').fillna(datetime.now().year).astype(int)

    # pick base_year (min) and last_year (max) from dataset
    base_year = int(df['tahun'].min())
    last_year = int(df['tahun'].max())

    # 3) prepare features & target
    # We'll one-hot encode categorical features: wilayah, kecamatan, jenis_kelamin
    feature_cols = [
        'jumlah_estimasi_penderita',
        'jumlah_yang_mendapatkan_pelayanan_kesehatan',
        'wilayah',
        'kecamatan',
        'jenis_kelamin'
    ]

    # fillna numeric columns
    df['jumlah_estimasi_penderita'] = pd.to_numeric(df['jumlah_estimasi_penderita'], errors='coerce').fillna(0)
    df['jumlah_yang_mendapatkan_pelayanan_kesehatan'] = pd.to_numeric(df['jumlah_yang_mendapatkan_pelayanan_kesehatan'], errors='coerce').fillna(0)

    features = pd.get_dummies(df[feature_cols], drop_first=True).fillna(0)
    target = df['persentase']

    model = None
    # 4) train or load model
    if target.notna().sum() > 10:
        # basic train-test split + training
        try:
            X_train, X_test, y_train, y_test = train_test_split(features, target, test_size=0.2, random_state=42)
            model = RandomForestRegressor(n_estimators=200, max_depth=12, random_state=42, n_jobs=-1)
            model.fit(X_train, y_train)
            print("✅ Trained RandomForest model on dataset.")
            if model_path:
                os.makedirs(os.path.dirname(model_path), exist_ok=True)
                joblib.dump(model, model_path)
                print(f"✅ Saved model to: {model_path}")
        except Exception as e:
            print("⚠️ Error training model:", e)
            model = None
    else:
        # try load existing model if provided
        if model_path and os.path.exists(model_path):
            try:
                model = joblib.load(model_path)
                print(f"✅ Loaded existing model from {model_path}")
            except Exception as e:
                print("⚠️ Failed to load existing model:", e)
                model = None
        else:
            print("⚠️ Not enough labeled rows to train model and no existing model found. Using fallback (mean).")
            model = None

    # 5) assemble df_all with existing rows first
    df_all = df[['kecamatan', 'wilayah', 'persentase', 'tahun']].copy()
    df_all['prioritas'] = df_all['persentase'].apply(lambda x: 'Prioritas' if pd.notna(x) and float(x) > 85 else 'Tidak')

    # 6) generate future predictions for next `years` after last_year
    if years < 1:
        years = 1
    future_years = list(range(last_year + 1, last_year + 1 + int(years)))

    if model is not None:
        # use model to predict per-year; prediction uses same static features (no dynamic trend) unless you provide time features
        for y in future_years:
            df_future = df.copy()
            df_future['tahun'] = y
            future_features = pd.get_dummies(df_future[feature_cols], drop_first=True)
            # ensure columns align to training features
            future_features = future_features.reindex(columns=features.columns, fill_value=0)
            try:
                preds = model.predict(future_features)
            except Exception as e:
                print("⚠️ Model predict error, falling back to mean:", e)
                preds = np.repeat(df['persentase'].fillna(df['persentase'].mean()).mean(), len(df_future))
            df_future['persentase'] = preds
            df_future['prioritas'] = df_future['persentase'].apply(lambda x: 'Prioritas' if float(x) > 85 else 'Tidak')
            df_all = pd.concat([df_all, df_future[['kecamatan', 'wilayah', 'persentase', 'tahun', 'prioritas']]], ignore_index=True)
    else:
        # fallback: repeat last known persentase or mean for each future year
        fallback_vals = df.groupby('kecamatan')['persentase'].last().fillna(df['persentase'].mean())
        for y in future_years:
            df_fb = df[['kecamatan', 'wilayah']].drop_duplicates(subset=['kecamatan']).copy()
            df_fb['tahun'] = y
            df_fb['persentase'] = df_fb['kecamatan'].map(fallback_vals).fillna(df['persentase'].mean())
            df_fb['prioritas'] = df_fb['persentase'].apply(lambda x: 'Prioritas' if float(x) > 85 else 'Tidak')
            df_all = pd.concat([df_all, df_fb[['kecamatan', 'wilayah', 'persentase', 'tahun', 'prioritas']]], ignore_index=True)

    # remove duplicates keeping the first (which will be the real dataset value if exists)
    df_all = df_all.drop_duplicates(subset=['kecamatan', 'tahun'], keep='first').reset_index(drop=True)

    # 7) merge geojson centroid lon/lat if provided
    if geojson and os.path.exists(geojson) and gpd is not None:
        try:
            gdf = gpd.read_file(geojson)
            # create a clean key for matching
            if 'NAME_3' in gdf.columns:
                gdf['NAME_3_clean'] = (
                    gdf['NAME_3'].astype(str)
                    .str.strip()
                    .str.lower()
                    .str.replace(r'[^a-z0-9]', '', regex=True)
                )
            else:
                # try other name columns fallback
                possible = [c for c in gdf.columns if 'name' in c.lower()]
                if possible:
                    gdf['NAME_3_clean'] = (
                        gdf[possible[0]].astype(str)
                        .str.strip()
                        .str.lower()
                        .str.replace(r'[^a-z0-9]', '', regex=True)
                    )
                else:
                    gdf['NAME_3_clean'] = ''

            df_all['kecamatan_clean'] = (
                df_all['kecamatan'].astype(str)
                .str.strip()
                .str.lower()
                .str.replace(r'[^a-z0-9]', '', regex=True)
            )

            # compute centroid lon/lat in WGS84
            gdf_proj = gdf.to_crs(epsg=3857)
            centroids = gdf_proj.geometry.centroid.to_crs(epsg=4326)
            gdf['lon'] = centroids.x
            gdf['lat'] = centroids.y

            geo_info = gdf[['NAME_3_clean', 'lon', 'lat']].drop_duplicates(subset=['NAME_3_clean'])
            merged = df_all.merge(geo_info, left_on='kecamatan_clean', right_on='NAME_3_clean', how='left')
            merged.drop(columns=['kecamatan_clean', 'NAME_3_clean'], inplace=True, errors='ignore')
            df_all = merged
            # report missing matches
            missing = df_all[df_all['lat'].isna()][['kecamatan', 'tahun']].drop_duplicates().head(30)
            if len(missing):
                print("❗ KECAMATAN TIDAK MATCH (contoh up to 30):")
                print(missing.to_string(index=False))
        except Exception as e:
            print("⚠️ Error reading/merging geojson:", e)
            # still proceed without lon/lat
            df_all['lon'] = None
            df_all['lat'] = None
    else:
        # no geo join available (either geojson not provided or geopandas not installed)
        df_all['lon'] = None
        df_all['lat'] = None
        if geojson and not os.path.exists(geojson):
            print(f"⚠️ GeoJSON path provided but not found: {geojson}")
        if geojson and gpd is None:
            print("⚠️ geopandas not installed; skipping geojson merge (install geopandas to enable).")

    # 8) add route/schedule
    df_out = generate_route_and_schedule(df_all)

    # 9) ensure output dir exists and write CSV
    os.makedirs(os.path.dirname(output_csv), exist_ok=True)
    df_out.to_csv(output_csv, index=False, encoding='utf-8')
    print("✅ Wrote predictions to", output_csv)

    # 10) print summary
    total_new = df_out['tahun'].nunique()
    print(f"ℹ️ Years present in output: {sorted(df_out['tahun'].unique())}")
    print(f"ℹ️ Total rows in output: {len(df_out)}")

# --- CLI --------------------------------------------------------------------

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Run ML prediction and produce future-year predictions.")
    parser.add_argument("--input_csv", required=False, help="Path to input CSV or XLSX (default: uploaded XLSX path)")
    parser.add_argument("--geojson", required=False, help="Path to geojson file for centroid matching (optional)")
    parser.add_argument("--output_csv", required=True, help="Path to output CSV predictions")
    parser.add_argument("--model_path", required=False, help="Path to save/load model (joblib). Optional.")
    parser.add_argument("--years", required=False, type=int, default=10, help="How many future years to predict (default 10).")
    args = parser.parse_args()

    try:
        main(args.input_csv, args.geojson, args.output_csv, args.model_path, args.years)
    except Exception as e:
        print("ERROR:", e)
        raise
