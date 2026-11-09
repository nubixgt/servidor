import type { Metadata } from 'next';
import LoginScreen from './login-screen';
import './login.css';
export const metadata: Metadata = {title:'Iniciar sesión · SANAFER 2026',description:'Pantalla de acceso al Baile Social SANAFER 2026.'};
export default function LoginPage(){return <LoginScreen/>;}
