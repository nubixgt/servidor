import pandas as pd
import time

dpis_to_search = [
    "3054338490207",
    "3054096820207",
    "2624432360805",
    "2623051860805",
    "1919833670207",
    "2681842912216",
    "1967380760207",
    "1795440980701",
    "3092593980805",
    "2054279460207",
    "3129100630807",
    "1628647950805",
    "1682253690207",
    "3095992450805",
    "1786645530805",
    "3110612220805",
    "3100025160805",
    "2543353700805",
    "2541644981406",
    "3263285131603",
    "1640704270101",
    "1961895861602",
    "2915171531602",
    "2306237351204",
    "2102201391204",
    "3929801932102",
    "1577370981406",
    "2342070861416",
    "3054024980207",
    "1865895650806",
    "1726845490201",
    "1701342022101",
    "2513287672101",
    "2877444181401",
    "2268277981409",
    "1619244841501",
    "3054279620207",
    "2756693271804",
    "1704458752101",
    "3054547490207",
    "1962228771602",
    "3252443671401",
    "3086915630805",
    "3054694380207",
    "1998560790101",
    "3316833771204",
    "3317289311204",
    "1890867690117",
    "2554287580805",
    "1629323910805",
    "3542797770207"
]

output_file = 'resultados_busqueda_dpi.xlsx'

print("Buscando DPIs...")
start = time.time()

chunk_size = 1000000
results = []

for chunk in pd.read_csv('ListadoGTCompleto.csv', dtype=str, chunksize=chunk_size):
    if 'DPI' in chunk.columns:
        # Avoid setting values on a slice, use copy
        clean_dpi = chunk['DPI'].str.replace(r'\D', '', regex=True)
        mask = clean_dpi.isin(dpis_to_search)
        matched = chunk[mask]
        if not matched.empty:
            results.append(matched)

if results:
    final_df = pd.concat(results, ignore_index=True)
    # The original DPI probably has spaces, we can add a column with clean DPI
    final_df['DPI_CLEAN'] = final_df['DPI'].str.replace(r'\D', '', regex=True)
    
    # Save the order from the original list if possible, or just export
    # Also deduplicate just in case? Or just export all matches
    final_df.to_excel(output_file, index=False)
    print(f"Se encontraron {len(final_df)} registros. Guardado en {output_file}")
    
    # print the names just to have a preview
    print(final_df[['NOMBRE', 'DPI_CLEAN']].to_string(index=False))
else:
    print("No se encontraron coincidencias para los DPIs solicitados.")

print(f"Time taken: {time.time() - start:.2f} seconds")
