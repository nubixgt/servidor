import { initialScores } from '../utils/rubrics';

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

// Foto de prueba (placeholder) mientras llega la foto real de Ardany Edwin Mendoza.
const PLACEHOLDER_IMAGE = {
  imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCz7mTo7bIDZUvOvxhFqt8kRBCxCcRj5QITpH1QI8CnwPQdZ5uCJp0hsJcMRgQHuixD3XL_3sjmyclYgvzWwbryP4w7nRLj2PuP-cpwYhUXjlAC0ejABEtHrt7piSVUAnLLeX1RuVLtCPvyXHyLOoXwBSkRfvf08nSjnRSdTmuJShVXtYGk0AGD0UmrrRM6C4DNWVimUBtjwKJm64KBZCd4lupM-8jd7FSTx-gNFm0SVzZQcq5recoq',
  avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAfaLcj0AaS-RYmD02RX7Rgw4cdvYN-Te4txhMEnon7o165KFlx7305UPfag0QiOQ7-uzE42LuIeBjgnzpv_EjH8oeYEjD_ccTbfnf4Awz2k71EqZmWg8FpQnkqM3lOYtAFzswhZKik9_W58GI4xhfmCLx2WM-7sBorggyWe7y9Fw3m7hN1IXZ462u2TKH-e00_lZ5HkPHF6oV_HZyp3eFhvKIuvuPOpZrqIKyH7gq0CCTDvReeSS75',
  podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAfaLcj0AaS-RYmD02RX7Rgw4cdvYN-Te4txhMEnon7o165KFlx7305UPfag0QiOQ7-uzE42LuIeBjgnzpv_EjH8oeYEjD_ccTbfnf4Awz2k71EqZmWg8FpQnkqM3lOYtAFzswhZKik9_W58GI4xhfmCLx2WM-7sBorggyWe7y9Fw3m7hN1IXZ462u2TKH-e00_lZ5HkPHF6oV_HZyp3eFhvKIuvuPOpZrqIKyH7gq0CCTDvReeSS75',
};

function photo(src) {
  return { imageUrl: src, avatarUrl: src, podiumUrl: src };
}

// Nombres y fotos tomados de "Copia de VOTACIONES JURADO.xlsx" (hojas FASHION SHOW / COREOGRAFIA / GALA / DATO FINAL),
// con la ortografía confirmada por las fotos oficiales en src/assets/images/ (impresa en cada retrato).
const SENORITAS = [
  { name: 'Luna Estefany Consuelo Arevalo Ramos', ...photo(EstefanyArevalo) },
  { name: 'Diana Luisa Alejandra Castro Velásquez', ...photo(AlejandraCastro) },
  { name: 'Madelyn Samara Hernandez Hernandez', ...photo(SamaraHernandez) },
  { name: 'Victoria Margarita Sharshente Gonzalez', ...photo(MargaritaGonzalez) },
  { name: 'Laisha Sofia Xitumul Ixcopal', ...photo(SofiaXitumul) },
  { name: 'Delmi Leonela Catalan Gonzalez', ...photo(LeonelaCatalan) },
  { name: 'Dayra Yamileth Gomez Ixpata', ...photo(YamilethGomez) },
  { name: 'Elba Dayanary Lopez Perez', ...photo(DayanaryLopez) },
  { name: 'Yaneli Alexandra Molineros Franco', ...photo(AlexandraMolineros) },
];

const JOVENES = [
  { name: 'Erik Josue Marroquin Villavicencio', ...photo(JosueMarroquin) },
  { name: 'Anthony Jose Salvatierra Rodriguez', ...photo(JoseSalvatierra) },
  { name: 'Edgar Julio Manuel Juarez Garcia', ...photo(ManuelJuarez) },
  { name: 'Nery Bagner Josué Tello Oliva', ...photo(BagnerOliva) },
  { name: 'Jefferson Gerrad Canahui Jeronimo', ...photo(GerradCanahui) },
  { name: 'Juan Pablo Hernandez Rodriguez', ...photo(PabloHernandez) },
  { name: 'Jeremy Alain Ebany Sarpec Ixtecoc', ...photo(AlainSarpec) },
  { name: 'Ardany Edwin Mendoza', ...PLACEHOLDER_IMAGE },
  { name: 'Cristhian Samuel Cuellar Flores', ...photo(SamuelCuellar) },
];

function buildParticipants(entries, category, prefix) {
  return entries.map((entry, i) => ({
    id: `${prefix}${String(i + 1).padStart(2, '0')}`,
    category,
    scores: initialScores(category),
    ...entry,
  }));
}

export const INITIAL_PARTICIPANTS = [
  ...buildParticipants(SENORITAS, 'SENORITA', 'SR'),
  ...buildParticipants(JOVENES, 'JOVEN', 'JV'),
];
