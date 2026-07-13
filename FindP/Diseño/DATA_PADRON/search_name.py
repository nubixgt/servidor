import pandas as pd
import time

name_to_search = "henry geovany dubon ruano"
output_file = 'busqueda_nombre.xlsx'

print(f"Buscando el registro para: {name_to_search}...")
start = time.time()

chunk_size = 1000000
results = []

try:
    for chunk in pd.read_csv('ListadoGTCompleto.csv', dtype=str, chunksize=chunk_size):
        if 'NOMBRE' in chunk.columns:
            # Case insensitive search
            mask = chunk['NOMBRE'].str.contains(name_to_search, case=False, na=False)
            matched = chunk[mask]
            if not matched.empty:
                results.append(matched)
                print(f"Encontrada coincidencia en un bloque...")

    if results:
        final_df = pd.concat(results, ignore_index=True)
        final_df.to_excel(output_file, index=False)
        print(f"\nSe encontraron {len(final_df)} registros.")
        print(final_df.to_string(index=False))
        print(f"\nResultados guardados en {output_file}")
    else:
        print(f"\nNo se encontraron registros para: {name_to_search}")

except Exception as e:
    print(f"Error durante la búsqueda: {e}")

print(f"Tiempo transcurrido: {time.time() - start:.2f} segundos")
