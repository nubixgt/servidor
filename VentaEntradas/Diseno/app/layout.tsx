import type { Metadata } from 'next';
import './globals.css';
export const metadata: Metadata = {title:'SANAFER — Tu lugar en la noche',description:'Elige tu mesa para el Baile Social SANAFER. Consulta el mapa, las localidades y la disponibilidad.',icons:{icon:'/sanafer-2026.png'}};
export default function RootLayout({children}:Readonly<{children:React.ReactNode}>){return <html lang="es"><body>{children}</body></html>}
