# ml/main_ml.py
import argparse
import pandas as pd
import geopandas as gpd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
import joblib
import os
from datetime import datetime

def normalize_kecamatan(s):
    if pd.isna(s):
        return s
    s = s.title().strip()
    corrections = {
        'Grogol Petamburan': 'Grogolpetamburan',
        'Kebon Jeruk': 'Kebonjeruk',
        'Pal Merah': 'Palmerah',
        'Pasar Rebo': 'Pasarrebo',
        'Taman Sari': 'Tamansari',
        'Tanah Abang': 'Tanahabang',
    }
    return corrections.get(s, s)

def generate_route_and_schedule(df):
    """
    Generate 'predicted_route' dan 'focus_date' per tahun
    berdasarkan prioritas (persentase tinggi = lebih cepat).
    """
    df = df.copy()

    # Buat skor prioritas (semakin tinggi persentase, semakin awal dikerjakan)
    df['score'] = df['persentase'] * 100 + np.random.rand(len(df)) * 5

    # Urutkan per tahun & prioritas
    df = df.sort_values(['tahun', 'score'], ascending=[True, False]).reset_index(drop=True)

    df['predicted_route'] = None
    df['focus_month'] = None
    df['focus_date'] = None

    for year, group in df.groupby('tahun'):
        group = group.reset_index()

        n = len(group)
        # Sebar ke 12 bulan → skor tertinggi = bulan 1
        month_assignments = np.linspace(1, 12, n, dtype=int)

        route_num = 1
        for i, idx in enumerate(group['index']):
            month = int(month_assignments[i])
            df.loc[idx, 'focus_month'] = month
            df.loc[idx, 'focus_date'] = f"{year}-{month:02d}-01"
            df.loc[idx, 'predicted_route'] = f"Rute-{route_num}"

            # tiap 8 kecamatan ganti rute
            if (i + 1) % 8 == 0:
                route_num += 1

    df.drop(columns=['score'], inplace=True)
    return df

def main(input_csv, geojson, output_csv, model_path=None):
    df = pd.read_csv(input_csv)

    # Normalisasi kecamatan
    df['kecamatan_clean'] = df['kecamatan'].astype(str).apply(normalize_kecamatan)
    df['kecamatan_final'] = df['kecamatan_clean']
    if 'tahun' not in df.columns:
        df['tahun'] = datetime.now().year

    # Base year dari dataset
    base_year = int(df['tahun'].min())

    # Fitur training
    features = pd.get_dummies(df[[
        'jumlah_estimasi_penderita',
        'jumlah_yang_mendapatkan_pelayanan_kesehatan',
        'wilayah',
        'kecamatan',
        'jenis_kelamin'
    ]], drop_first=True).fillna(0)

    target = df['persentase']

    model = RandomForestRegressor(n_estimators=100, max_depth=10, random_state=42)
    try:
        if target.notna().sum() > 10:
            X_train, X_test, y_train, y_test = train_test_split(
                features, target, test_size=0.2, random_state=42
            )
            model.fit(X_train, y_train)
            if model_path:
                os.makedirs(os.path.dirname(model_path), exist_ok=True)
                joblib.dump(model, model_path)
        elif model_path and os.path.exists(model_path):
            model = joblib.load(model_path)
        else:
            df['persentase_pred'] = df['persentase'].fillna(df['persentase'].mean())
            df['prioritas'] = df['persentase_pred'].apply(
                lambda x: 'Prioritas' if x > 85 else 'Tidak'
            )
            model = None
    except Exception as e:
        print("Error training model:", e)
        model = None

    # Data asli
    df_all = df[['kecamatan_final', 'wilayah', 'persentase', 'tahun']].copy()
    df_all['prioritas'] = df_all['persentase'].apply(
        lambda x: "Prioritas" if x > 85 else "Tidak"
    )

    # Data prediksi masa depan
    if model is not None:
        for year in range(base_year + 1, base_year + 7):  # misal 2025–2030
            df_future = df.copy()
            df_future['tahun'] = year
            future_features = pd.get_dummies(df_future[[
                'jumlah_estimasi_penderita',
                'jumlah_yang_mendapatkan_pelayanan_kesehatan',
                'wilayah',
                'kecamatan',
                'jenis_kelamin'
            ]], drop_first=True)
            future_features = future_features.reindex(columns=features.columns, fill_value=0)
            try:
                preds = model.predict(future_features)
            except Exception:
                preds = np.repeat(df['persentase'].fillna(0).mean(), len(df_future))
            df_future['persentase'] = preds
            df_future['kecamatan_final'] = df_future['kecamatan_clean']
            df_future['prioritas'] = df_future['persentase'].apply(
                lambda x: "Prioritas" if x > 85 else "Tidak"
            )
            df_all = pd.concat([
                df_all,
                df_future[['kecamatan_final', 'wilayah', 'persentase', 'tahun', 'prioritas']]
            ], ignore_index=True)

    # Unik per tahun + kecamatan
    df_all = df_all.drop_duplicates(subset=['kecamatan_final', 'tahun']).reset_index(drop=True)

    # Tambah geojson
    if geojson and os.path.exists(geojson):
        gdf = gpd.read_file(geojson)
        gdf['NAME_3_clean'] = gdf['NAME_3'].astype(str).str.strip()
        gdf = gdf.to_crs(epsg=4326)
        gdf['lon'] = gdf.geometry.centroid.x
        gdf['lat'] = gdf.geometry.centroid.y
        geo_info = gdf[['NAME_3_clean', 'lon', 'lat']]
        df_all = df_all.merge(
            geo_info, left_on='kecamatan_final', right_on='NAME_3_clean', how='left'
        )
        if 'NAME_3_clean' in df_all.columns:
            df_all.drop(columns=['NAME_3_clean'], inplace=True)

    # Tambah route & schedule berbasis prioritas
    df_all = generate_route_and_schedule(df_all)

    os.makedirs(os.path.dirname(output_csv), exist_ok=True)
    df_all.to_csv(output_csv, index=False, encoding='utf-8')
    print("✅ Wrote predictions to", output_csv)

if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--input_csv", required=True, help="Path to input CSV (converted from dataset.xlsx)")
    parser.add_argument("--geojson", required=False, help="Path to penelitian.geojson")
    parser.add_argument("--output_csv", required=True, help="Path to write predictions CSV")
    parser.add_argument("--model_path", required=False, help="Optional path to save model joblib")
    args = parser.parse_args()
    main(args.input_csv, args.geojson, args.output_csv, args.model_path)
