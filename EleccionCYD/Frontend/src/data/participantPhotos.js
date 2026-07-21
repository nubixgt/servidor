// Las fotos siguen viviendo en el Frontend (no se subieron al Backend). Este mapa conecta
// cada foto con el "codigo" del participante en la base de datos (SR01..SR09, JV01..JV09),
// que es el mismo código con el que se cargó la tabla `participantes` (Database/schema.sql).
import EstefanyArevalo from '../assets/images/EstefanyArevalo.jpg';
import AlejandraCastro from '../assets/images/AlejandraCastro.jpg';
import SamaraHernandez from '../assets/images/SamaraHernandez.jpg';
import MargaritaGonzalez from '../assets/images/MargaritaGonzalez.jpg';
import SofiaXitumul from '../assets/images/SofiaXitumul.jpg';
import LeonelaCatalan from '../assets/images/LeonelaCatalan.jpg';
import YamilethGomez from '../assets/images/YamilethGomez.jpg';
import DayanaryLopez from '../assets/images/DayanaryLopez.jpg';
import AlexandraMolineros from '../assets/images/AlexandraMolineros.jpg';

import JosueMarroquin from '../assets/images/JosueMarroquin.jpg';
import JoseSalvatierra from '../assets/images/JoseSalvatierra.jpg';
import ManuelJuarez from '../assets/images/ManuelJuarez.jpg';
import BagnerOliva from '../assets/images/BagnerOliva.jpg';
import GerradCanahui from '../assets/images/GerradCanahui.jpg';
import PabloHernandez from '../assets/images/PabloHernandez.jpg';
import AlainSarpec from '../assets/images/AlainSarpec.jpg';
import SamuelCuellar from '../assets/images/SamuelCuellar.jpg';
import PlaceholderLogo from '../assets/images/PlaceholderLogo.jpg';

export const PARTICIPANT_PHOTOS = {
  SR01: EstefanyArevalo,
  SR02: AlejandraCastro,
  SR03: SamaraHernandez,
  SR04: MargaritaGonzalez,
  SR05: SofiaXitumul,
  SR06: LeonelaCatalan,
  SR07: YamilethGomez,
  SR08: DayanaryLopez,
  SR09: AlexandraMolineros,

  JV01: JosueMarroquin,
  JV02: JoseSalvatierra,
  JV03: ManuelJuarez,
  JV04: BagnerOliva,
  JV05: GerradCanahui,
  JV06: PabloHernandez,
  JV07: AlainSarpec,
  JV08: PlaceholderLogo, // Ardany Edwin Mendoza -- pendiente de foto real
  JV09: SamuelCuellar,
};
