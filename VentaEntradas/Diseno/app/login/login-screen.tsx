'use client';
import {useState, type FormEvent} from 'react';
import {ArrowLeft,ArrowRight,Eye,EyeOff,LockKeyhole,UserRound,Ticket,Info,ChevronDown} from 'lucide-react';

/** Pantalla visual: conectar handleSubmit a la autenticación del servidor. */
export default function LoginScreen(){
 const [showPassword,setShowPassword]=useState(false);
 const [showHelp,setShowHelp]=useState(false);
 const [notice,setNotice]=useState('');
 function handleSubmit(event:FormEvent<HTMLFormElement>){
  event.preventDefault();
  // La demostración no envía, guarda ni valida credenciales reales.
  // Sustituir por la API de acceso del proyecto antes de habilitar sesiones.
  setNotice('El inicio de sesión todavía no está habilitado en esta vista previa. Puedes volver al mapa para explorar las mesas.');
 }
 return <div className="login-page">
  <header className="login-header"><a href="/" className="brand" aria-label="SANAFER, inicio"><img className="brand-logo" src="/sanafer-2026.png" alt="Logo de SANAFER 2026" width="52" height="52"/>SANAFER<span className="brand-sub">BAILE<br/>SOCIAL</span></a><a className="login-back" href="/"><ArrowLeft size={16}/><span>Volver al mapa</span></a></header>
  <main className="login-main"><div className="login-shell">
   <section className="login-story" aria-label="Baile Social SANAFER 2026">
    <div className="login-story-top"><span className="eyebrow"><i/> FERIA SANAFER 2026</span><span className="login-edition">BAILE SOCIAL</span></div>
    <div className="login-logo-art"><img src="/sanafer-2026.png" alt="Feria SANAFER 2026: tradición y celebración" width="380" height="380" fetchPriority="high"/></div>
    <div className="login-story-copy"><h2>La mejor noche<br/><em>empieza contigo.</em></h2><p>Buena música, buena compañía<br/>y un lugar para compartir.</p></div>
    <div className="login-story-bottom"><Ticket size={19} strokeWidth={1.4}/><span>Una mesa. Ocho historias. Una gran noche.</span><span className="login-star" aria-hidden="true">✳</span></div>
   </section>
   <section className="login-form-panel" aria-labelledby="login-heading"><div className="login-form-inner">
    <span className="login-key"><LockKeyhole size={23} strokeWidth={1.5}/></span><span className="eyebrow login-welcome">QUÉ BUENO TENERTE AQUÍ</span>
    <h1 id="login-heading">Bienvenido<br/><em>de nuevo.</em></h1><p className="login-intro">Ingresa a tu cuenta para continuar.</p>
    <form className="login-form" onSubmit={handleSubmit}>
     <div className="login-field"><label htmlFor="login-user">Usuario o correo electrónico</label><div className="login-input-wrap"><UserRound size={18} strokeWidth={1.5}/><input id="login-user" name="username" type="text" autoComplete="username" placeholder="Escribe tu usuario o correo" maxLength={254} required onChange={()=>setNotice('')}/></div></div>
     <div className="login-field"><label htmlFor="login-password">Contraseña</label><div className="login-input-wrap"><LockKeyhole size={18} strokeWidth={1.5}/><input id="login-password" name="password" type={showPassword?'text':'password'} autoComplete="current-password" placeholder="Escribe tu contraseña" required onChange={()=>setNotice('')}/><button className="login-password-toggle" type="button" aria-label={showPassword?'Ocultar contraseña':'Mostrar contraseña'} aria-pressed={showPassword} onClick={()=>setShowPassword(!showPassword)}>{showPassword?<EyeOff size={18}/>:<Eye size={18}/>}</button></div></div>
     <div className="login-options"><button type="button" className="login-help-toggle" aria-expanded={showHelp} aria-controls="login-help" onClick={()=>setShowHelp(!showHelp)}>¿Olvidaste tu contraseña?<ChevronDown size={13}/></button></div>
     {showHelp&&<p className="login-help-message" id="login-help">Solicita la recuperación de tu acceso al administrador del evento. En esta vista previa no se envían correos de recuperación.</p>}
     {notice&&<div className="login-notice" role="status"><Info size={18}/><p>{notice}</p></div>}
     <button className="primary login-submit" type="submit">Iniciar sesión<ArrowRight size={18}/></button>
    </form>
    <div className="login-divider"><span/><span>LA NOCHE TE ESPERA</span><span/></div>
    <a href="/" className="login-explore">Explorar las mesas<ArrowRight size={15}/></a>
    <div className="login-preview-note"><span>VISTA PREVIA</span><p>Formulario de demostración.<br/>No se envían ni guardan credenciales.</p></div>
   </div></section>
  </div></main>
  <footer className="login-footer"><span>SANAFER <b>2026</b></span><span>Buena música. Buena compañía. Una gran noche.</span></footer>
 </div>;
}
