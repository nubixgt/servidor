"""Los movimientos que la app puede recetar, con su imagen de referencia.

## Por que existe

El atleta leia "bicho muerto" en el movil, en el gimnasio, entre serie y serie,
y no sabia que hacer. El plan describia el ejercicio con palabras y nada mas.
Esto le pone una imagen al lado.

## La regla que gobierna este modulo: ABSTENERSE ANTES QUE ACERTAR POR SUERTE

Ensenarle al atleta la imagen de OTRO ejercicio es peor que no ensenarle
ninguna: haria el movimiento equivocado creyendo que hace el suyo, que es la
version de gimnasio del fallo que este repo persigue -- la forma correcta con
el contenido equivocado. Por eso `resolver()` empareja **exacto contra la lista
de alias** y devuelve `None` en cuanto duda. Sin coincidencia parcial, sin
distancia de edicion, sin "se parece bastante".

Los alias incluyen a proposito las MALAS traducciones ("bicho muerto",
"perro pajaro", "escultura"): si el modelo escribe una, la imagen que sale
sigue siendo la correcta. Estan aqui porque ya las hemos visto salir.

## Como llega el nombre correcto

`video_key` lo escribe el codificador de entrenos, y el prompt le da esta misma
lista cerrada para elegir (`render_catalogo_para_prompt`), igual que se hace
con `VERIFIED_EXERCISES` de Garmin. Si aun asi inventa uno, `resolver()`
reintenta con el `name` del ejercicio y sus alias; si tampoco, no hay imagen y
la sesion se lee como se leia antes.

## Las imagenes

`Frontend/public/ejercicios/NNN.jpg`, numeradas por la posicion en este
catalogo. Son diagramas de tres fases (A/B/C) con el nombre en espanol e ingles
y una linea de "para que". Se generaron para el MVP a coste minimo: existen
para medir si vale la pena producir video, no para ser la version final.

El numero es la clave estable, no el nombre: un ejercicio puede cambiar de
nombre y su imagen sigue siendo la misma.
"""

from __future__ import annotations

import re
import unicodedata


def normalizar(texto: str | None) -> str:
    """Un nombre libre convertido a la forma con la que se compara.

    Sin acentos, sin mayusculas, sin puntuacion: "Sentadilla Bulgara",
    "sentadilla-bulgara" y "SENTADILLA_BULGARA" son el mismo ejercicio, y el
    modelo escribe las tres.
    """
    if not texto:
        return ""
    plano = unicodedata.normalize("NFD", texto).encode("ascii", "ignore").decode()
    # El apostrofo se BORRA, no se convierte en separador: "farmer's carry" y
    # "farmers carry" son lo mismo y el modelo escribe las dos. Convirtiendolo
    # en "_" salian `farmer_s_carry` y `farmers_carry`, que no se encontraban.
    plano = re.sub(r"['‘’`´]", "", plano)
    return re.sub(r"[^a-zA-Z0-9]+", "_", plano).strip("_").lower()


# slug -> (numero, nombre en espanol, nombre en ingles, grupo, subgrupo)
EJERCICIOS: dict[str, tuple[int, str, str, str, str]] = {
    "peso_muerto_rumano": (1, "Peso muerto rumano", "Romanian deadlift (RDL)", "fuerza", "Bisagra de cadera y cadena posterior"),
    "peso_muerto_convencional": (2, "Peso muerto convencional", "Conventional deadlift", "fuerza", "Bisagra de cadera y cadena posterior"),
    "peso_muerto_rumano_a_una_pierna": (3, "Peso muerto rumano a una pierna", "Single-leg Romanian deadlift", "fuerza", "Bisagra de cadera y cadena posterior"),
    "puente_de_gluteo": (4, "Puente de glúteo", "Glute bridge", "fuerza", "Bisagra de cadera y cadena posterior"),
    "puente_a_una_pierna": (5, "Puente a una pierna", "Single-leg glute bridge", "fuerza", "Bisagra de cadera y cadena posterior"),
    "hip_thrust_con_barra": (6, "Hip thrust con barra", "Barbell hip thrust", "fuerza", "Bisagra de cadera y cadena posterior"),
    "curl_nordico": (7, "Curl nórdico", "Nordic hamstring curl", "fuerza", "Bisagra de cadera y cadena posterior"),
    "buenos_dias": (8, "Buenos días", "Good morning", "fuerza", "Bisagra de cadera y cadena posterior"),
    "swing_con_kettlebell": (9, "Swing con kettlebell", "Kettlebell swing", "fuerza", "Bisagra de cadera y cadena posterior"),
    "sentadilla_trasera_con_barra": (10, "Sentadilla trasera con barra", "Barbell back squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "sentadilla_goblet": (11, "Sentadilla goblet", "Goblet squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "sentadilla_frontal": (12, "Sentadilla frontal", "Front squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "sentadilla_bulgara": (13, "Sentadilla búlgara", "Bulgarian split squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "sentadilla_dividida": (14, "Sentadilla dividida", "Split squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "subida_al_cajon": (15, "Subida al cajón", "Step-up", "fuerza", "Sentadilla y dominante de rodilla"),
    "prensa_de_piernas": (16, "Prensa de piernas", "Leg press", "fuerza", "Sentadilla y dominante de rodilla"),
    "sentadilla_a_una_pierna": (17, "Sentadilla a una pierna", "Pistol squat", "fuerza", "Sentadilla y dominante de rodilla"),
    "zancada_caminando": (18, "Zancada caminando", "Walking lunge", "fuerza", "Zancadas y trabajo unilateral"),
    "zancada_inversa": (19, "Zancada inversa", "Reverse lunge", "fuerza", "Zancadas y trabajo unilateral"),
    "zancada_lateral": (20, "Zancada lateral", "Lateral lunge", "fuerza", "Zancadas y trabajo unilateral"),
    "zancada_con_elevacion_de_rodilla": (21, "Zancada con elevación de rodilla", "Lunge with knee lift", "fuerza", "Zancadas y trabajo unilateral"),
    "zancada_con_peso_sobre_la_cabeza": (22, "Zancada con peso sobre la cabeza", "Overhead lunge", "fuerza", "Zancadas y trabajo unilateral"),
    "press_de_banca": (23, "Press de banca", "Barbell bench press", "fuerza", "Empuje"),
    "flexion": (24, "Flexión", "Push-up", "fuerza", "Empuje"),
    "flexion_spiderman": (25, "Flexión spiderman", "Spiderman push-up", "fuerza", "Empuje"),
    "flexion_en_t": (26, "Flexión en T", "T push-up", "fuerza", "Empuje"),
    "press_de_hombro_con_mancuernas": (27, "Press de hombro con mancuernas", "Dumbbell shoulder press", "fuerza", "Empuje"),
    "press_de_hombro_alterno": (28, "Press de hombro alterno", "Alternating dumbbell shoulder press", "fuerza", "Empuje"),
    "fondos_en_paralelas": (29, "Fondos en paralelas", "Dips", "fuerza", "Empuje"),
    "press_inclinado_con_mancuernas": (30, "Press inclinado con mancuernas", "Incline dumbbell press", "fuerza", "Empuje"),
    "dominada": (31, "Dominada", "Pull-up", "fuerza", "Tracción"),
    "dominada_supina": (32, "Dominada supina", "Chin-up", "fuerza", "Tracción"),
    "remo_con_mancuerna": (33, "Remo con mancuerna", "One-arm dumbbell row", "fuerza", "Tracción"),
    "remo_renegado": (34, "Remo renegado", "Renegade row", "fuerza", "Tracción"),
    "remo_con_barra": (35, "Remo con barra", "Barbell bent-over row", "fuerza", "Tracción"),
    "jalon_al_pecho": (36, "Jalón al pecho", "Lat pulldown", "fuerza", "Tracción"),
    "face_pull": (37, "Face pull", "Face pull", "fuerza", "Tracción"),
    "curl_de_biceps_con_mancuernas": (38, "Curl de bíceps con mancuernas", "Dumbbell biceps curl", "fuerza", "Tracción"),
    "paseo_del_granjero": (39, "Paseo del granjero", "Farmer's carry", "fuerza", "Acarreos"),
    "acarreo_en_maleta": (40, "Acarreo en maleta", "Suitcase carry", "fuerza", "Acarreos"),
    "acarreo_sobre_la_cabeza": (41, "Acarreo sobre la cabeza", "Overhead carry", "fuerza", "Acarreos"),
    "salto_al_cajon": (42, "Salto al cajón", "Box jump", "fuerza", "Pliometría y reactividad"),
    "salto_en_sentadilla": (43, "Salto en sentadilla", "Squat jump", "fuerza", "Pliometría y reactividad"),
    "squat_jacks": (44, "Squat jacks", "Squat jacks", "fuerza", "Pliometría y reactividad"),
    "pogos": (45, "Pogos", "Pogo hops", "fuerza", "Pliometría y reactividad"),
    "salto_de_patinador": (46, "Salto de patinador", "Skater jump", "fuerza", "Pliometría y reactividad"),
    "multisaltos_a_una_pierna": (47, "Multisaltos a una pierna", "Single-leg hops", "fuerza", "Pliometría y reactividad"),
    "drop_jump": (48, "Drop jump", "Drop jump", "fuerza", "Pliometría y reactividad"),
    "rotacion_externa_con_banda": (49, "Rotación externa con banda", "Band external rotation", "fuerza", "Prehab de hombro (nadador)"),
    "elevaciones_y_t_w": (50, "Elevaciones Y-T-W", "Prone Y-T-W raises", "fuerza", "Prehab de hombro (nadador)"),
    "flexion_escapular": (51, "Flexión escapular", "Scapular push-up", "fuerza", "Prehab de hombro (nadador)"),
    "aperturas_con_banda": (52, "Aperturas con banda", "Band pull-apart", "fuerza", "Prehab de hombro (nadador)"),
    "rotacion_de_brazo_en_semiarrodillado": (53, "Rotación de brazo en semiarrodillado", "Half-kneeling arm rotation", "fuerza", "Prehab de hombro (nadador)"),
    "elevacion_lateral": (54, "Elevación lateral", "Lateral raise", "fuerza", "Prehab de hombro (nadador)"),
    "deslizamiento_en_pared": (55, "Deslizamiento en pared", "Wall slide", "fuerza", "Prehab de hombro (nadador)"),
    "almeja": (56, "Almeja", "Clamshell", "fuerza", "Prehab de cadera y glúteo medio"),
    "marcha_lateral_con_banda": (57, "Marcha lateral con banda", "Banded lateral walk", "fuerza", "Prehab de cadera y glúteo medio"),
    "abduccion_tumbado_de_lado": (58, "Abducción tumbado de lado", "Side-lying hip abduction", "fuerza", "Prehab de cadera y glúteo medio"),
    "elevacion_de_pierna_en_rotacion_externa": (59, "Elevación de pierna en rotación externa", "Leg lift in external rotation", "fuerza", "Prehab de cadera y glúteo medio"),
    "circulos_de_pierna": (60, "Círculos de pierna", "Leg circles", "fuerza", "Prehab de cadera y glúteo medio"),
    "plancha_de_copenhague": (61, "Plancha de Copenhague", "Copenhagen plank", "fuerza", "Prehab de cadera y glúteo medio"),
    "elevacion_de_talones_de_pie": (62, "Elevación de talones de pie", "Standing calf raise", "fuerza", "Pie, tobillo y gemelo"),
    "elevacion_de_talones_sentado": (63, "Elevación de talones sentado", "Seated calf raise", "fuerza", "Pie, tobillo y gemelo"),
    "elevacion_de_talon_a_una_pierna": (64, "Elevación de talón a una pierna", "Single-leg calf raise", "fuerza", "Pie, tobillo y gemelo"),
    "excentrico_de_gemelo_en_escalon": (65, "Excéntrico de gemelo en escalón", "Eccentric heel drop", "fuerza", "Pie, tobillo y gemelo"),
    "dorsiflexion_con_banda": (66, "Dorsiflexión con banda", "Banded ankle dorsiflexion", "fuerza", "Pie, tobillo y gemelo"),
    "arco_corto": (67, "Arco corto", "Short foot exercise", "fuerza", "Pie, tobillo y gemelo"),
    "caminata_de_talones_y_puntas": (68, "Caminata de talones y puntas", "Heel-toe walk", "fuerza", "Pie, tobillo y gemelo"),
    "plancha_frontal": (69, "Plancha frontal", "Front plank", "movilidad", "Core anti-extensión"),
    "dead_bug": (70, "Dead bug", "Dead bug", "movilidad", "Core anti-extensión"),
    "rueda_abdominal": (71, "Rueda abdominal", "Ab wheel rollout", "movilidad", "Core anti-extensión"),
    "escalador_cruzado": (72, "Escalador cruzado", "Cross-body mountain climber", "movilidad", "Core anti-extensión"),
    "hollow_hold": (73, "Hollow hold", "Hollow hold", "movilidad", "Core anti-extensión"),
    "plancha_lateral": (74, "Plancha lateral", "Side plank", "movilidad", "Core antirrotación"),
    "plancha_lateral_con_elevacion_de_pierna": (75, "Plancha lateral con elevación de pierna", "Side plank with leg lift", "movilidad", "Core antirrotación"),
    "pallof_press": (76, "Pallof press", "Pallof press", "movilidad", "Core antirrotación"),
    "bird_dog": (77, "Bird dog", "Bird dog", "movilidad", "Core antirrotación"),
    "giro_ruso": (78, "Giro ruso", "Russian twist", "movilidad", "Core antirrotación"),
    "bicicleta_abdominal": (79, "Bicicleta abdominal", "Bicycle crunch", "movilidad", "Core antirrotación"),
    "criss_cross": (80, "Criss-cross", "Criss cross", "movilidad", "Core antirrotación"),
    "gato_camello": (81, "Gato-camello", "Cat-cow", "movilidad", "Movilidad"),
    "postura_del_nino": (82, "Postura del niño", "Child's pose", "movilidad", "Movilidad"),
    "enhebrar_la_aguja": (83, "Enhebrar la aguja", "Thread the needle", "movilidad", "Movilidad"),
    "cobra": (84, "Cobra", "Cobra", "movilidad", "Movilidad"),
    "giro_espinal": (85, "Giro espinal", "Spinal twist", "movilidad", "Movilidad"),
    "estiramiento_de_cadera_90_90": (86, "Estiramiento de cadera 90/90", "90/90 hip stretch", "movilidad", "Movilidad"),
    "estiramiento_de_isquios_con_banda": (87, "Estiramiento de isquios con banda", "Hamstring stretch with band", "movilidad", "Movilidad"),
    "rana_con_banda": (88, "Rana con banda", "Frog with band", "movilidad", "Movilidad"),
    "estiramiento_lateral_de_pie": (89, "Estiramiento lateral de pie", "Standing side stretch", "movilidad", "Movilidad"),
    "estiramiento_de_flexor_de_cadera": (90, "Estiramiento de flexor de cadera", "Half-kneeling hip flexor stretch", "movilidad", "Movilidad"),
    "sentadilla_profunda_sostenida": (91, "Sentadilla profunda sostenida", "Deep squat hold", "movilidad", "Movilidad"),
    "roll_up": (92, "Roll up", "Roll up", "movilidad", "Core (pilates, ya en Garmin)"),
    "el_cien": (93, "El cien", "The hundred", "movilidad", "Core (pilates, ya en Garmin)"),
    "estiramiento_de_una_pierna": (94, "Estiramiento de una pierna", "Single leg stretch", "movilidad", "Core (pilates, ya en Garmin)"),
    "estiramiento_de_dos_piernas": (95, "Estiramiento de dos piernas", "Double leg stretch", "movilidad", "Core (pilates, ya en Garmin)"),
    "tijeras": (96, "Tijeras", "Scissors", "movilidad", "Core (pilates, ya en Garmin)"),
    "cisne": (97, "Cisne", "Swan", "movilidad", "Core (pilates, ya en Garmin)"),
    "patada_con_tabla": (98, "Patada con tabla", "Kick with kickboard", "natacion", "Técnica de posición y rotación"),
    "patada_de_costado": (99, "Patada de costado", "Side kicking", "natacion", "Técnica de posición y rotación"),
    "6_1_6": (100, "6-1-6", "Six-kick switch", "natacion", "Técnica de posición y rotación"),
    "flecha": (101, "Flecha", "Streamline push-off", "natacion", "Técnica de posición y rotación"),
    "catch_up": (102, "Catch-up", "Catch-up drill", "natacion", "Técnica de brazada"),
    "arrastre_de_dedos": (103, "Arrastre de dedos", "Fingertip drag", "natacion", "Técnica de brazada"),
    "nado_con_punos": (104, "Nado con puños", "Fist drill", "natacion", "Técnica de brazada"),
    "un_brazo": (105, "Un brazo", "Single-arm drill", "natacion", "Técnica de brazada"),
    "sculling": (106, "Sculling", "Sculling", "natacion", "Técnica de brazada"),
    "brazada_con_pull_buoy": (107, "Brazada con pull buoy", "Pull with buoy", "natacion", "Técnica de brazada"),
    "palas": (108, "Palas", "Paddles", "natacion", "Técnica de brazada"),
    "aletas": (109, "Aletas", "Fins", "natacion", "Material y respiración"),
    "tubo_frontal": (110, "Tubo frontal", "Front snorkel", "natacion", "Material y respiración"),
    "respiracion_bilateral": (111, "Respiración bilateral", "Bilateral breathing", "natacion", "Material y respiración"),
    "nado_con_banda_en_tobillos": (112, "Nado con banda en tobillos", "Ankle band swim", "natacion", "Material y respiración"),
    "orientacion": (113, "Orientación", "Sighting", "natacion", "Aguas abiertas y viraje"),
    "crol_con_cabeza_fuera": (114, "Crol con cabeza fuera", "Heads-up freestyle", "natacion", "Aguas abiertas y viraje"),
    "voltereta": (115, "Voltereta", "Flip turn", "natacion", "Aguas abiertas y viraje"),
    "viraje_abierto": (116, "Viraje abierto", "Open turn", "natacion", "Aguas abiertas y viraje"),
    "skipping_a": (117, "Skipping A", "A-skip", "carrera", "Técnica"),
    "skipping_b": (118, "Skipping B", "B-skip", "carrera", "Técnica"),
    "talones_al_gluteo": (119, "Talones al glúteo", "Butt kicks", "carrera", "Técnica"),
    "marcha_de_tobillo": (120, "Marcha de tobillo", "Ankling", "carrera", "Técnica"),
    "paso_cruzado": (121, "Paso cruzado", "Carioca", "carrera", "Técnica"),
    "caida_hacia_delante": (122, "Caída hacia delante", "Lean drill", "carrera", "Técnica"),
    "rectas_progresivas": (123, "Rectas progresivas", "Strides", "carrera", "Intensidad y reactividad"),
    "cuestas_cortas": (124, "Cuestas cortas", "Short hill sprints", "carrera", "Intensidad y reactividad"),
    "multisaltos_alternos": (125, "Multisaltos alternos", "Bounding", "carrera", "Intensidad y reactividad"),
    "trabajo_de_cadencia": (126, "Trabajo de cadencia", "Cadence drill", "carrera", "Intensidad y reactividad"),
}

# Todo lo que puede llegar escrito y significa uno de los de arriba: el nombre
# canonico, el ingles, los sinonimos corrientes y las traducciones malas.
ALIAS: dict[str, str] = {
    "6_patadas_1_brazada": "6_1_6",
    "90_90": "estiramiento_de_cadera_90_90",
    "90_90_hip_stretch": "estiramiento_de_cadera_90_90",
    "a_skip": "skipping_a",
    "ab_wheel_rollout": "rueda_abdominal",
    "acarreo_del_granjero": "paseo_del_granjero",
    "alternating_dumbbell_shoulder_press": "press_de_hombro_alterno",
    "ankle_band_swim": "nado_con_banda_en_tobillos",
    "ankling": "marcha_de_tobillo",
    "antirrotacion_en_polea": "pallof_press",
    "b_skip": "skipping_b",
    "back_squat": "sentadilla_trasera_con_barra",
    "balanceo_con_kettlebell": "swing_con_kettlebell",
    "banca": "press_de_banca",
    "band_external_rotation": "rotacion_externa_con_banda",
    "band_pull_apart": "aperturas_con_banda",
    "banda_en_tobillos": "nado_con_banda_en_tobillos",
    "banded_ankle_dorsiflexion": "dorsiflexion_con_banda",
    "banded_lateral_walk": "marcha_lateral_con_banda",
    "barbell_back_squat": "sentadilla_trasera_con_barra",
    "barbell_bench_press": "press_de_banca",
    "barbell_bent_over_row": "remo_con_barra",
    "barbell_hip_thrust": "hip_thrust_con_barra",
    "barquita": "hollow_hold",
    "barra": "dominada",
    "bicho_muerto": "dead_bug",
    "bicicleta": "bicicleta_abdominal",
    "bicycle_crunch": "bicicleta_abdominal",
    "bilateral_breathing": "respiracion_bilateral",
    "bounding": "multisaltos_alternos",
    "box_jump": "salto_al_cajon",
    "brazada_a_un_brazo": "un_brazo",
    "brincos": "pogos",
    "bulgarian_split_squat": "sentadilla_bulgara",
    "butt_kicks": "talones_al_gluteo",
    "cadence_drill": "trabajo_de_cadencia",
    "cadencia": "trabajo_de_cadencia",
    "caida_de_talon": "excentrico_de_gemelo_en_escalon",
    "carioca": "paso_cruzado",
    "cat_cow": "gato_camello",
    "catch_up_drill": "catch_up",
    "chapaletas": "aletas",
    "child_s_pose": "postura_del_nino",
    "childs_pose": "postura_del_nino",
    "chin_up": "dominada_supina",
    "circunducciones_de_cadera": "circulos_de_pierna",
    "clam": "almeja",
    "clams": "almeja",
    "clamshell": "almeja",
    "cocodrilo": "orientacion",
    "concha": "almeja",
    "conventional_deadlift": "peso_muerto_convencional",
    "copenhagen": "plancha_de_copenhague",
    "copenhagen_plank": "plancha_de_copenhague",
    "cremallera": "arrastre_de_dedos",
    "cross_body_mountain_climber": "escalador_cruzado",
    "cuestas": "cuestas_cortas",
    "culo_talon": "talones_al_gluteo",
    "curl": "curl_de_biceps_con_mancuernas",
    "curl_de_biceps": "curl_de_biceps_con_mancuernas",
    "curl_femoral_nordico": "curl_nordico",
    "deadlift": "peso_muerto_convencional",
    "dedo_por_la_axila": "arrastre_de_dedos",
    "deep_squat_hold": "sentadilla_profunda_sostenida",
    "dips": "fondos_en_paralelas",
    "dislocaciones_con_banda": "aperturas_con_banda",
    "dominadas": "dominada",
    "double_leg_stretch": "estiramiento_de_dos_piernas",
    "drill_de_inclinacion": "caida_hacia_delante",
    "dumbbell_biceps_curl": "curl_de_biceps_con_mancuernas",
    "dumbbell_shoulder_press": "press_de_hombro_con_mancuernas",
    "eccentric_heel_drop": "excentrico_de_gemelo_en_escalon",
    "elevacion_con_rotacion": "elevacion_de_pierna_en_rotacion_externa",
    "elevacion_de_cadera": "puente_de_gluteo",
    "elevacion_de_gemelos": "elevacion_de_talones_de_pie",
    "elevacion_de_pelvis_con_barra": "hip_thrust_con_barra",
    "elevacion_de_pierna_de_lado": "abduccion_tumbado_de_lado",
    "elevacion_de_rodillas": "skipping_a",
    "elevaciones_laterales": "elevacion_lateral",
    "empuje_de_cadera": "hip_thrust_con_barra",
    "enrollamiento": "roll_up",
    "escalador": "escalador_cruzado",
    "escarabajo_muerto": "dead_bug",
    "escultura": "sculling",
    "estiramiento_de_cuadriceps_arrodillado": "estiramiento_de_flexor_de_cadera",
    "estiramiento_de_isquios": "estiramiento_de_isquios_con_banda",
    "estiramiento_del_psoas": "estiramiento_de_flexor_de_cadera",
    "estiramiento_lateral": "estiramiento_lateral_de_pie",
    "extension_contralateral_en_cuadrupedia": "bird_dog",
    "farmer_s_carry": "paseo_del_granjero",
    "farmer_s_walk": "paseo_del_granjero",
    "farmers_carry": "paseo_del_granjero",
    "fingertip_drag": "arrastre_de_dedos",
    "fins": "aletas",
    "fist_drill": "nado_con_punos",
    "flexion_arana": "flexion_spiderman",
    "flexion_con_rotacion": "flexion_en_t",
    "flexion_de_brazos": "flexion",
    "flip_turn": "voltereta",
    "fondos": "fondos_en_paralelas",
    "frog_with_band": "rana_con_banda",
    "front_plank": "plancha_frontal",
    "front_snorkel": "tubo_frontal",
    "front_squat": "sentadilla_frontal",
    "gato_vaca": "gato_camello",
    "gemelo_sentado": "elevacion_de_talones_sentado",
    "gemelo_unipodal": "elevacion_de_talon_a_una_pierna",
    "glute_bridge": "puente_de_gluteo",
    "goblet_squat": "sentadilla_goblet",
    "good_morning": "buenos_dias",
    "grapevine": "paso_cruzado",
    "half_kneeling_arm_rotation": "rotacion_de_brazo_en_semiarrodillado",
    "half_kneeling_hip_flexor_stretch": "estiramiento_de_flexor_de_cadera",
    "hamstring_stretch_with_band": "estiramiento_de_isquios_con_banda",
    "heads_up_freestyle": "crol_con_cabeza_fuera",
    "heel_toe_walk": "caminata_de_talones_y_puntas",
    "incline_dumbbell_press": "press_inclinado_con_mancuernas",
    "insecto_muerto": "dead_bug",
    "jalon": "jalon_al_pecho",
    "jalon_a_la_cara": "face_pull",
    "kettlebell_swing": "swing_con_kettlebell",
    "kick_with_kickboard": "patada_con_tabla",
    "lagartija": "flexion",
    "lat_pulldown": "jalon_al_pecho",
    "lateral_bound": "salto_de_patinador",
    "lateral_lunge": "zancada_lateral",
    "lateral_raise": "elevacion_lateral",
    "lean_drill": "caida_hacia_delante",
    "leg_circles": "circulos_de_pierna",
    "leg_lift_in_external_rotation": "elevacion_de_pierna_en_rotacion_externa",
    "leg_press": "prensa_de_piernas",
    "los_cien": "el_cien",
    "lunge": "zancada_caminando",
    "lunge_with_knee_lift": "zancada_con_elevacion_de_rodilla",
    "manoplas": "palas",
    "marcha_de_talones": "caminata_de_talones_y_puntas",
    "marcha_de_tobillos": "marcha_de_tobillo",
    "marcha_del_cangrejo": "marcha_lateral_con_banda",
    "metronomo": "trabajo_de_cadencia",
    "monster_walk": "marcha_lateral_con_banda",
    "mountain_climber_cruzado": "escalador_cruzado",
    "movilizacion_de_tobillo": "dorsiflexion_con_banda",
    "nado_con_pull": "brazada_con_pull_buoy",
    "nordic_hamstring_curl": "curl_nordico",
    "nordico": "curl_nordico",
    "noventa_noventa": "estiramiento_de_cadera_90_90",
    "one_arm_dumbbell_row": "remo_con_mancuerna",
    "open_turn": "viraje_abierto",
    "overhead_carry": "acarreo_sobre_la_cabeza",
    "overhead_lunge": "zancada_con_peso_sobre_la_cabeza",
    "paddles": "palas",
    "pajaro_perro": "bird_dog",
    "palas_de_mano": "palas",
    "paseo_de_la_maleta": "acarreo_en_maleta",
    "paseo_del_camarero": "acarreo_sobre_la_cabeza",
    "paseo_del_campesino": "paseo_del_granjero",
    "paso_de_monstruo": "marcha_lateral_con_banda",
    "patada_de_lado": "patada_de_costado",
    "patada_lateral": "patada_de_costado",
    "perro_pajaro": "bird_dog",
    "peso_muerto": "peso_muerto_convencional",
    "peso_muerto_a_una_pierna": "peso_muerto_rumano_a_una_pierna",
    "peso_muerto_piernas_rigidas": "peso_muerto_rumano",
    "peso_muerto_rumano_unipodal": "peso_muerto_rumano_a_una_pierna",
    "pie_corto": "arco_corto",
    "piernas_con_tabla": "patada_con_tabla",
    "pistol": "sentadilla_a_una_pierna",
    "pistol_squat": "sentadilla_a_una_pierna",
    "plancha": "flexion",
    "plancha_abdominal": "plancha_frontal",
    "plancha_de_aductores": "plancha_de_copenhague",
    "plancha_de_costado": "plancha_lateral",
    "plancha_lateral_con_abduccion": "plancha_lateral_con_elevacion_de_pierna",
    "plank": "plancha_frontal",
    "pogo_hops": "pogos",
    "polea_al_pecho": "jalon_al_pecho",
    "polo_drill": "crol_con_cabeza_fuera",
    "posicion_de_flecha": "flecha",
    "posicion_hueca": "hollow_hold",
    "postura_de_la_rana": "rana_con_banda",
    "prensa": "prensa_de_piernas",
    "press_alterno": "press_de_hombro_alterno",
    "press_banca": "press_de_banca",
    "press_de_hombros": "press_de_hombro_con_mancuernas",
    "press_inclinado": "press_inclinado_con_mancuernas",
    "press_militar": "press_de_hombro_con_mancuernas",
    "press_pallof": "pallof_press",
    "progresivos": "rectas_progresivas",
    "prone_y_t_w_raises": "elevaciones_y_t_w",
    "protocolo_alfredson": "excentrico_de_gemelo_en_escalon",
    "puente_de_cadera": "puente_de_gluteo",
    "puente_unipodal": "puente_a_una_pierna",
    "pull_up": "dominada",
    "pull_with_buoy": "brazada_con_pull_buoy",
    "puno_cerrado": "nado_con_punos",
    "puntillas": "elevacion_de_talones_de_pie",
    "punto_muerto": "catch_up",
    "push_up": "flexion",
    "push_up_plus": "flexion_escapular",
    "rdl": "peso_muerto_rumano",
    "rdl_unipodal": "peso_muerto_rumano_a_una_pierna",
    "rectas": "rectas_progresivas",
    "remada": "sculling",
    "remo_a_una_mano": "remo_con_mancuerna",
    "remo_con_apoyo": "remo_con_mancuerna",
    "remo_en_plancha": "remo_renegado",
    "remo_inclinado": "remo_con_barra",
    "renegade_row": "remo_renegado",
    "repeticiones_en_cuesta": "cuestas_cortas",
    "respiracion_3": "respiracion_bilateral",
    "respirar_a_los_dos_lados": "respiracion_bilateral",
    "reverse_lunge": "zancada_inversa",
    "rodilla_alta": "skipping_a",
    "rollout": "rueda_abdominal",
    "romanian_deadlift_rdl": "peso_muerto_rumano",
    "rotacion_externa_de_hombro": "rotacion_externa_con_banda",
    "rotacion_toracica_arrodillado": "rotacion_de_brazo_en_semiarrodillado",
    "rueda": "rueda_abdominal",
    "russian_twist": "giro_ruso",
    "salida_de_pared": "flecha",
    "salto_al_banco": "salto_al_cajon",
    "salto_en_caida": "drop_jump",
    "salto_lateral": "salto_de_patinador",
    "saltos_alternos": "multisaltos_alternos",
    "saltos_de_tijera_en_sentadilla": "squat_jacks",
    "saltos_de_tobillo": "pogos",
    "saltos_unipodales": "multisaltos_a_una_pierna",
    "scapular_push_up": "flexion_escapular",
    "scissors": "tijeras",
    "seated_calf_raise": "elevacion_de_talones_sentado",
    "sentadilla_anterior": "sentadilla_frontal",
    "sentadilla_con_barra": "sentadilla_trasera_con_barra",
    "sentadilla_con_mancuerna_al_pecho": "sentadilla_goblet",
    "sentadilla_con_salto": "salto_en_sentadilla",
    "sentadilla_copa": "sentadilla_goblet",
    "sentadilla_dividida_con_pie_elevado": "sentadilla_bulgara",
    "sentadilla_pistola": "sentadilla_a_una_pierna",
    "sentadilla_profunda": "sentadilla_profunda_sostenida",
    "short_foot": "arco_corto",
    "short_foot_exercise": "arco_corto",
    "short_hill_sprints": "cuestas_cortas",
    "shoulder_sweeps": "aperturas_con_banda",
    "side_kicking": "patada_de_costado",
    "side_lunge": "zancada_lateral",
    "side_lying_hip_abduction": "abduccion_tumbado_de_lado",
    "side_plank": "plancha_lateral",
    "side_plank_with_leg_lift": "plancha_lateral_con_elevacion_de_pierna",
    "sighting": "orientacion",
    "single_arm_drill": "un_brazo",
    "single_leg_calf_raise": "elevacion_de_talon_a_una_pierna",
    "single_leg_glute_bridge": "puente_a_una_pierna",
    "single_leg_hops": "multisaltos_a_una_pierna",
    "single_leg_romanian_deadlift": "peso_muerto_rumano_a_una_pierna",
    "single_leg_stretch": "estiramiento_de_una_pierna",
    "six_kick_switch": "6_1_6",
    "skater_jump": "salto_de_patinador",
    "skipping": "skipping_a",
    "snorkel": "tubo_frontal",
    "solo_brazos": "brazada_con_pull_buoy",
    "spiderman_push_up": "flexion_spiderman",
    "spinal_twist": "giro_espinal",
    "split_squat": "sentadilla_dividida",
    "squat_jump": "salto_en_sentadilla",
    "standing_calf_raise": "elevacion_de_talones_de_pie",
    "standing_side_stretch": "estiramiento_lateral_de_pie",
    "step_up": "subida_al_cajon",
    "streamline_push_off": "flecha",
    "strides": "rectas_progresivas",
    "subida_al_escalon": "subida_al_cajon",
    "suitcase_carry": "acarreo_en_maleta",
    "swan": "cisne",
    "swing_ruso": "swing_con_kettlebell",
    "t_push_up": "flexion_en_t",
    "talon_gluteo": "talones_al_gluteo",
    "the_hundred": "el_cien",
    "thread_the_needle": "enhebrar_la_aguja",
    "tiron_a_la_cara": "face_pull",
    "toca_y_sigue": "catch_up",
    "torsion_espinal": "giro_espinal",
    "torsion_rusa": "giro_ruso",
    "tubo": "tubo_frontal",
    "un_solo_brazo": "un_brazo",
    "viraje": "voltereta",
    "viraje_de_pared": "viraje_abierto",
    "viraje_olimpico": "voltereta",
    "viraje_simple": "viraje_abierto",
    "waiter_s_walk": "acarreo_sobre_la_cabeza",
    "walking_lunge": "zancada_caminando",
    "wall_slide": "deslizamiento_en_pared",
    "wall_slides": "deslizamiento_en_pared",
    "water_polo": "crol_con_cabeza_fuera",
    "y_t_w": "elevaciones_y_t_w",
    "ytw": "elevaciones_y_t_w",
    "zancada": "zancada_caminando",
    "zancada_al_lado": "zancada_lateral",
    "zancada_atras": "zancada_inversa",
    "zancada_bulgara": "sentadilla_bulgara",
    "zancada_con_rodilla_arriba": "zancada_con_elevacion_de_rodilla",
    "zancada_estatica": "sentadilla_dividida",
    "zancada_overhead": "zancada_con_peso_sobre_la_cabeza",
    "zipper": "arrastre_de_dedos",
}

# Los que todavia no tienen imagen. El catalogo los conoce igual -- el nombre y
# el grupo siguen sirviendo -- pero `imagen_de` devuelve None y la UI no pinta
# nada en vez de pintar un hueco roto.
#
# Ahora mismo esta VACIO, y ese es el estado sano. Llego a tener el 86
# (estiramiento de cadera 90/90): el generador de imagenes se lo salto porque el
# nombre del fichero habria llevado una barra, que no vale en un nombre de
# fichero. Se rehizo como `086_Estiramiento_de_cadera_90-90.jpg`.
#
# Se deja el mecanismo aunque este vacio: la proxima vez que se anada un
# ejercicio al catalogo tendra numero antes que imagen, y hay que poder decirlo
# sin romper nada.
SIN_IMAGEN: frozenset[int] = frozenset()


def resolver(nombre: str | None = None, video_key: str | None = None) -> str | None:
    """El slug canonico del ejercicio, o None si no se puede afirmar cual es.

    Se prueba primero `video_key`, que es el campo que el prompt pide elegir de
    la lista, y despues el nombre visible. Cualquiera de los dos vale; los dos
    se comparan EXACTO contra los alias.
    """
    for candidato in (video_key, nombre):
        clave = normalizar(candidato)
        if not clave:
            continue
        for forma in (clave, _en_singular(clave), _en_singular(clave, solo_la_primera=True)):
            if forma in EJERCICIOS:
                return forma
            destino = ALIAS.get(forma)
            if destino:
                return destino
    return None


def _en_singular(clave: str, solo_la_primera: bool = False) -> str:
    """La misma clave con cada palabra en singular: "flexiones" -> "flexion".

    La gente escribe su rutina en plural ("Flexiones", "Zancadas atrás", lectura del
    2026-09-23) y los alias están en singular. Sólo plurales regulares, palabra a
    palabra, y el resultado se sigue comparando EXACTO contra la lista.
    """
    def singular(p: str) -> str:
        if p.endswith("iones"):
            return p[:-5] + "ion"
        if p.endswith("ces") and len(p) > 4:
            return p[:-3] + "z"
        if p.endswith("s") and len(p) > 3 and not p.endswith("ss"):
            return p[:-1]
        return p
    palabras = clave.split("_")
    if solo_la_primera:  # "zancadas_atras": el sustantivo, no el adverbio
        return "_".join([singular(palabras[0])] + palabras[1:])
    return "_".join(singular(p) for p in palabras)


def nombre_de(slug: str | None) -> str | None:
    """El nombre en castellano de una clave del catálogo, o None si no es una.

    Existe porque un modelo que elige de la lista a veces escribe la CLAVE donde
    iba el nombre ("arco_corto" en vez de "Arco corto"), y el atleta lee eso en el
    gimnasio. Traducirlo es exacto: la clave ya identifica el ejercicio.
    """
    entrada = EJERCICIOS.get(slug or "")
    return entrada[1] if entrada else None


def numero_de(slug: str | None) -> int | None:
    """La posicion en el catalogo, que es la clave estable de la imagen."""
    entrada = EJERCICIOS.get(slug or "")
    return entrada[0] if entrada else None


def imagen_de(slug: str | None) -> str | None:
    """El nombre del fichero de imagen ("013.jpg"), o None si no hay."""
    n = numero_de(slug)
    if n is None or n in SIN_IMAGEN:
        return None
    return f"{n:03d}.jpg"


# ── Garmin, desde el catálogo ────────────────────────────────────────────────
#
# Atleta 2, auditoría del 2026-09-18: el modelo que ordena la rutina pegada elegía
# también el nombre de Garmin, y "Extensión de cuádriceps" llegó al reloj como
# HAMSTRING_CURL (un estiramiento de la categoría MOVE) en sus dos sesiones de
# pierna, mientras "Press de banca" -- que este catálogo reconoce -- se quedaba sin
# nombre. Cuando el catálogo SABE qué ejercicio es, el nombre de Garmin sale de
# aquí y no del modelo. Sólo parejas que volvieron intactas de un entreno real
# (`garmin_vocabulary.VERIFIED_EXERCISES`); una pareja dudosa no entra:
# "hip thrust con barra" no es BARBELL_HIP_THRUST_ON_FLOOR, y una sentadilla
# dividida sin mancuernas no es DUMBBELL_SPLIT_SQUAT.
GARMIN: dict[str, tuple[str, str]] = {
    "sentadilla_trasera_con_barra": ("SQUAT", "BARBELL_BACK_SQUAT"),
    "sentadilla_goblet": ("SQUAT", "GOBLET_SQUAT"),
    "prensa_de_piernas": ("SQUAT", "LEG_PRESS"),
    "peso_muerto_convencional": ("DEADLIFT", "BARBELL_DEADLIFT"),
    "peso_muerto_rumano": ("DEADLIFT", "ROMANIAN_DEADLIFT"),
    "zancada_con_peso_sobre_la_cabeza": ("LUNGE", "OVERHEAD_DUMBBELL_LUNGE"),
    "zancada_con_elevacion_de_rodilla": ("MOVE", "LUNGES_WITH_KNEE_LIFT"),
    "elevacion_de_talones_de_pie": ("CALF_RAISE", "STANDING_CALF_RAISE"),
    "press_de_banca": ("BENCH_PRESS", "BARBELL_BENCH_PRESS"),
    "press_inclinado_con_mancuernas": ("BENCH_PRESS", "INCLINE_DUMBBELL_BENCH_PRESS"),
    "press_de_hombro_con_mancuernas": ("SHOULDER_PRESS", "DUMBBELL_SHOULDER_PRESS"),
    "press_de_hombro_alterno": ("SHOULDER_PRESS", "ALTERNATING_DUMBBELL_SHOULDER_PRESS"),
    "flexion_spiderman": ("PUSH_UP", "SPIDERMAN_PUSH_UP"),
    "flexion_en_t": ("PUSH_UP", "T_PUSH_UP"),
    "dominada": ("PULL_UP", "PULL_UP"),
    "jalon_al_pecho": ("PULL_UP", "LAT_PULLDOWN"),
    "remo_renegado": ("ROW", "RENEGADE_ROW"),
    "face_pull": ("ROW", "FACE_PULL"),
    "curl_de_biceps_con_mancuernas": ("CURL", "DUMBBELL_BICEPS_CURL"),
    "paseo_del_granjero": ("CARRY", "FARMERS_CARRY"),
    "elevacion_lateral": ("LATERAL_RAISE", "DUMBBELL_LATERAL_RAISE"),
    "squat_jacks": ("CARDIO", "SQUAT_JACKS"),
    "almeja": ("HIP_RAISE", "CLAMS"),
    "circulos_de_pierna": ("HIP_RAISE", "LEG_CIRCLES"),
    "elevacion_de_pierna_en_rotacion_externa": ("HIP_RAISE", "LEG_LIFT_IN_EXTERNAL_ROTATION"),
    "rotacion_de_brazo_en_semiarrodillado": ("WARM_UP", "HALF_KNEELING_ARM_ROTATION"),
}

# Movimientos de gimnasio que Garmin NO tiene (medido con 1701096031): van sin
# nombre ni categoría, y el reloj enseña la descripción. Mejor eso que el nombre de
# otro ejercicio.
_SIN_EQUIVALENTE_EN_GARMIN = ("extension de cuadricep", "extensiones de cuadricep", "extension de pierna",
                              "extensiones de pierna", "maquina de abductor", "maquina de aductor")

# Las familias que un gimnasio no usa para un ejercicio de fuerza: son de pilates,
# movilidad y calentamiento. Un modelo que pone ahí un ejercicio de máquina se
# equivocó de ejercicio (HAMSTRING_CURL de MOVE es un estiramiento).
_FAMILIAS_DE_MOVILIDAD = frozenset({"MOVE", "POSE", "WARM_UP"})


def garmin_de(nombre: str | None, categoria: str | None = None,
              ejercicio: str | None = None) -> tuple[str | None, str | None]:
    """(categoría, nombre) de Garmin para un ejercicio: el catálogo manda sobre el modelo.

    - Un movimiento que Garmin no tiene: (None, None).
    - Si el catálogo lo reconoce y tiene su pareja verificada, ésa. Salvo que el modelo
      eligiera una pareja verificada de la MISMA familia, que es igual de segura y
      suele ser más precisa: "Press militar" es MILITARY_PRESS, y el catálogo sólo
      sabe que es un press de hombro.
    - Si el catálogo no lo conoce, lo que eligió el modelo, sólo si es una pareja
      verificada y no es una familia de movilidad.
    """
    from services.garmin import garmin_vocabulary

    plano = normalizar(nombre).replace("_", " ")
    if any(frase in plano for frase in _SIN_EQUIVALENTE_EN_GARMIN):
        return None, None
    familia = garmin_vocabulary.normalise_category(categoria)
    del_modelo = garmin_vocabulary.verified_exercise(familia, ejercicio)
    slug = resolver(nombre)
    if slug in GARMIN:
        if del_modelo and familia == GARMIN[slug][0] and _mismo_material(plano, del_modelo):
            return familia, del_modelo
        return GARMIN[slug]
    if familia in _FAMILIAS_DE_MOVILIDAD and slug is None:
        return None, None
    # El catálogo SABE qué ejercicio es y Garmin no tiene su nombre verificado: el del
    # modelo es de otro ejercicio. Lectura real del 2026-09-23: "Flexiones" llegaba como
    # PILATES_PUSHUP y "Zancadas atrás" como ALTERNATING_DUMBBELL_LUNGE. Va la familia.
    if slug is not None:
        return familia, None
    return familia, (del_modelo if del_modelo and _mismo_material(plano, del_modelo) else None)


def _mismo_material(nombre_plano: str, garmin: str) -> bool:
    """Que el nombre de Garmin no contradiga el material que escribió el atleta.

    "curl biceps barra" llegaba como DUMBBELL_BICEPS_CURL (lectura del 2026-09-23).
    """
    con_barra = any(p in nombre_plano for p in ("barra", "barbell"))
    con_mancuerna = any(p in nombre_plano for p in ("mancuerna", "dumbbell"))
    if con_barra and garmin.startswith("DUMBBELL"):
        return False
    if con_mancuerna and garmin.startswith("BARBELL"):
        return False
    # Los calificativos que hacen OTRO ejercicio tienen que estar en los dos nombres o en
    # ninguno. Lectura real del 2026-09-23: "curl biceps barra" llegó como
    # EZ_BAR_PREACHER_CURL (predicador) y "martillo" como DUMBBELL_BICEPS_CURL.
    for en_garmin, en_espanol in _CALIFICATIVOS:
        if (en_garmin in garmin) != any(p in nombre_plano for p in en_espanol):
            return False
    return True


_CALIFICATIVOS = (
    ("PREACHER", ("predicador", "scott")),
    ("INCLINE", ("inclinad", "incline")),
    ("DECLINE", ("declinad", "decline")),
    ("HAMMER", ("martillo", "hammer")),
    ("ALTERNATING", ("altern",)),
    ("REVERSE", ("invers", "revers")),
    ("SINGLE_ARM", ("un brazo", "una mano", "single arm")),
)


# ── Qué carga cada ejercicio ─────────────────────────────────────────────────
#
# El énfasis de una sesión (pierna, torso, completo, core) decide dónde cae en la
# semana de un atleta de resistencia. Las sesiones confirmadas antes de que
# existiera ese campo no lo tienen, y el paso de fuerza las trataba como cuerpo
# completo: "«Upper B» carga piernas" en la semana del atleta 2 (2026-09-18).
PIERNA, TORSO, CORE = "pierna", "torso", "core"

_REGION_DEL_SUBGRUPO = {
    "Bisagra de cadera y cadena posterior": PIERNA,
    "Sentadilla y dominante de rodilla": PIERNA,
    "Zancadas y trabajo unilateral": PIERNA,
    "Pliometría y reactividad": PIERNA,
    "Pie, tobillo y gemelo": PIERNA,
    "Prehab de cadera y glúteo medio": PIERNA,
    "Empuje": TORSO,
    "Tracción": TORSO,
    "Prehab de hombro (nadador)": TORSO,
    "Core anti-extensión": CORE,
    "Core antirrotación": CORE,
    "Core (pilates, ya en Garmin)": CORE,
}

# Para lo que el catálogo no tiene (las máquinas de un gimnasio). Pierna se mira
# PRIMERO: "curl femoral" es pierna aunque "curl" sea de brazo.
_PALABRAS_DE_PIERNA = ("sentadilla", "squat", "prensa", "zancada", "lunge", "peso muerto", "deadlift", "rdl",
                       "hip thrust", "gluteo", "gemelo", "talon", "pantorrilla", "cuadriceps", "femoral",
                       "isquio", "pierna", "bulgara", "step up", "cajon", "abductor", "aductor", "hack",
                       "salto", "leg ")
_PALABRAS_DE_TORSO = ("press", "banca", "pecho", "remo", "dominada", "jalon", "pull", "push", "flexion",
                      "hombro", "lateral", "biceps", "triceps", "curl", "apertura", "fondos", "militar",
                      "deltoid", "pullover", "face")
_PALABRAS_DE_CORE = ("plancha", "crunch", "abdominal", "dead bug", "bicho muerto", "pallof", "rueda", "core",
                     "hollow", "oblicuo", "russian", "rotacion")


def region_de(nombre: str | None) -> str | None:
    """pierna, torso o core; None si no se puede afirmar."""
    slug = resolver(nombre)
    if slug:
        region = _REGION_DEL_SUBGRUPO.get(EJERCICIOS[slug][4])
        if region:
            return region
    plano = f" {normalizar(nombre).replace('_', ' ')} "
    for palabras, region in ((_PALABRAS_DE_PIERNA, PIERNA), (_PALABRAS_DE_TORSO, TORSO),
                             (_PALABRAS_DE_CORE, CORE)):
        if any(p in plano for p in palabras):
            return region
    return None


def render_catalogo_para_prompt(grupos: tuple[str, ...] = ("fuerza", "movilidad")) -> str:
    """La lista cerrada de la que el modelo tiene que elegir `video_key`.

    Por defecto solo fuerza y movilidad: son los grupos que viajan dentro de un
    `strength_block`. Los drills de natacion y carrera se piden aparte, cuando
    el paso los necesita, para no gastar contexto en cada sesion.
    """
    por_sub: dict[str, list[str]] = {}
    for slug, (_n, es, _en, grupo, sub) in EJERCICIOS.items():
        if grupo in grupos:
            por_sub.setdefault(sub, []).append(f"{slug} ({es})")
    if not por_sub:
        return ""
    lineas = ["### Claves de ejercicio disponibles (`video_key`)", ""]
    for sub in por_sub:
        lineas.append(f"**{sub}**: " + "; ".join(sorted(por_sub[sub])))
    lineas += [
        "",
        "Elige `video_key` de esta lista SOLO si es realmente ese movimiento. Si el que "
        "prescribes no esta, deja `video_key` vacio y describelo en `cues` -- inventar una "
        "clave parecida le ensena al atleta la imagen de otro ejercicio, que es peor que no "
        "ensenarle ninguna. Nunca deformes la sesion para que encaje en la lista.",
    ]
    return "\n".join(lineas)
