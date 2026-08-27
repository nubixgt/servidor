/**
 * CYD Premium Effects — Cursor spotlight, smooth nav scroll, page transitions
 * Importar en main.js para efectos globales
 */
import { gsap } from '@/lib/gsap.js'
import { Observer } from 'gsap/Observer'

// ── Cursor Premium (Ring + Dot) ────────────────────────────────
export function initCursorSpotlight() {
  // Deshabilitar en dispositivos móviles / pantallas táctiles
  if (typeof window !== 'undefined' && window.matchMedia("(pointer: coarse)").matches) {
    return;
  }

  // Ocultar el cursor nativo
  document.body.style.cursor = 'none'
  const styleEl = document.createElement('style')
  styleEl.innerHTML = `* { cursor: none !important; }`
  document.head.appendChild(styleEl)

  // 1. El Anillo Exterior (Ring) - sigue con suavidad
  const ring = document.createElement('div')
  ring.id = 'cyd-cursor-ring'
  ring.style.cssText = `
    position: fixed;
    top: 0; left: 0;
    width: 36px; height: 36px;
    border: 1.5px solid var(--cyd-green);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9998;
    transform: translate(-50%, -50%);
    transition: width 0.3s ease, height 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
    will-change: transform;
    opacity: 0;
  `
  
  // 2. El Dot (puntero exacto) - sigue instantáneamente
  const dot = document.createElement('div')
  dot.id = 'cyd-cursor-dot'
  dot.style.cssText = `
    position: fixed;
    top: 0; left: 0;
    width: 6px; height: 6px;
    background: var(--cyd-green);
    border-radius: 50%;
    pointer-events: none;
    z-index: 9999;
    transform: translate(-50%, -50%);
    will-change: transform;
    opacity: 0;
    transition: transform 0.2s ease, background 0.3s ease;
  `

  document.body.appendChild(ring)
  document.body.appendChild(dot)

  let mouseX = 0, mouseY = 0
  let ringX = 0, ringY = 0
  let isHovering = false

  document.addEventListener('mousemove', (e) => {
    mouseX = e.clientX
    mouseY = e.clientY
    ring.style.opacity = '1'
    dot.style.opacity = '1'
    
    // El dot sigue instantáneamente
    dot.style.left = mouseX + 'px'
    dot.style.top = mouseY + 'px'
  })

  document.addEventListener('mouseleave', () => {
    ring.style.opacity = '0'
    dot.style.opacity = '0'
  })

  // Interacciones con elementos clicables
  const addHoverEffects = () => {
    const interactables = document.querySelectorAll('a, button, input, textarea, select, .cursor-pointer')
    interactables.forEach(el => {
      // Evitar agregar multiples event listeners
      if (el.dataset.cursorBound) return;
      el.dataset.cursorBound = "true";

      el.addEventListener('mouseenter', () => {
        isHovering = true
        // El anillo crece y se vuelve dorado semi-transparente
        ring.style.width = '60px'
        ring.style.height = '60px'
        ring.style.borderColor = 'var(--cyd-gold)'
        ring.style.backgroundColor = 'color-mix(in srgb, var(--cyd-gold) 15%, transparent)'
        
        // El dot se vuelve dorado
        dot.style.background = 'var(--cyd-gold)'
        dot.style.transform = 'translate(-50%, -50%) scale(1.5)'
      })
      el.addEventListener('mouseleave', () => {
        isHovering = false
        // Regresa a la normalidad
        ring.style.width = '36px'
        ring.style.height = '36px'
        ring.style.borderColor = 'var(--cyd-green)'
        ring.style.backgroundColor = 'transparent'
        
        dot.style.background = 'var(--cyd-green)'
        dot.style.transform = 'translate(-50%, -50%) scale(1)'
      })
    })
  }

  // Ejecutar inicialmente y usar MutationObserver para elementos agregados dinámicamente
  addHoverEffects()
  setTimeout(addHoverEffects, 1000)

  // Suavizar el anillo con GSAP ticker
  gsap.ticker.add(() => {
    // Si está en hover, el anillo sigue más de cerca, sino tiene más retraso
    const speed = isHovering ? 0.25 : 0.15
    ringX += (mouseX - ringX) * speed
    ringY += (mouseY - ringY) * speed
    ring.style.left = ringX + 'px'
    ring.style.top = ringY + 'px'
  })

  return ring
}

// ── Smooth Section Navigation con GSAP ───────────────────
export function smoothScrollTo(id) {
  const target = document.getElementById(id)
  if (!target) return

  const targetY = target.getBoundingClientRect().top + window.scrollY - 72

  gsap.to(window, {
    scrollTo: { y: targetY, autoKill: false },
    duration: 1.2,
    ease: 'power4.inOut',
  })
}

// ── Section Enter Animation con ScrollTrigger ────────────
export function initSectionReveal() {
  // Revelar secciones con clip-path al entrar
  const sections = document.querySelectorAll('section[id]')
  sections.forEach((section) => {
    // Línea de acento superior que aparece
    const line = document.createElement('div')
    line.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      width: 0%;
      height: 2px;
      background: linear-gradient(90deg, var(--cyd-green), var(--cyd-gold));
      z-index: 5;
      border-radius: 0 999px 999px 0;
    `
    section.style.position = 'relative'
    section.appendChild(line)

    gsap.to(line, {
      width: '40%',
      duration: 1.2,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: section,
        start: 'top 80%',
        once: true,
      },
    })
  })
}

// ── Magnetic Cards ────────────────────────────────────────
export function initMagneticCards(selector = '.cyd-card') {
  if (typeof window !== 'undefined' && window.matchMedia("(pointer: coarse)").matches) {
    return;
  }
  
  document.querySelectorAll(selector).forEach((card) => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect()
      const x = e.clientX - rect.left - rect.width / 2
      const y = e.clientY - rect.top - rect.height / 2
      const rotX = (-y / rect.height) * 8
      const rotY = (x / rect.width) * 8

      gsap.to(card, {
        rotateX: rotX,
        rotateY: rotY,
        transformPerspective: 800,
        duration: 0.4,
        ease: 'power2.out',
      })
    })

    card.addEventListener('mouseleave', () => {
      gsap.to(card, {
        rotateX: 0,
        rotateY: 0,
        duration: 0.6,
        ease: 'elastic.out(1, 0.4)',
      })
    })
  })
}
