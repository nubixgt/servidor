/**
 * GSAP Centralized Configuration — WEB-CYD
 * Registra plugins y expone helpers reutilizables
 */
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import { ScrollToPlugin } from 'gsap/ScrollToPlugin'
import { Observer } from 'gsap/Observer'

gsap.registerPlugin(ScrollTrigger, ScrollToPlugin, Observer)


// Defaults globales
gsap.defaults({
  ease: 'power3.out',
  duration: 0.9,
})

ScrollTrigger.defaults({
  toggleActions: 'play none none reverse',
  start: 'top 85%',
})

/**
 * Anima un elemento entrando desde abajo
 * @param {Element|string} target - elemento o selector
 * @param {Object} opts - opciones extra de GSAP
 */
export function animateIn(target, opts = {}) {
  return gsap.from(target, {
    opacity: 0,
    y: 60,
    duration: 1,
    ease: 'power3.out',
    ...opts,
  })
}

/**
 * Anima múltiples elementos con stagger
 * @param {string} selector - selector CSS
 * @param {Object} opts - opciones
 */
export function staggerIn(selector, opts = {}) {
  return gsap.from(selector, {
    opacity: 0,
    y: 50,
    duration: 0.8,
    stagger: 0.12,
    ease: 'power3.out',
    ...opts,
  })
}

/**
 * Crea un parallax layer con ScrollTrigger
 * @param {Element|string} trigger - elemento contenedor
 * @param {Element|string} target - elemento a mover
 * @param {number} speed - multiplicador de velocidad (0.1–0.5 recomendado)
 */
export function parallaxLayer(trigger, target, speed = 0.3) {
  return gsap.to(target, {
    yPercent: speed * 100,
    ease: 'none',
    scrollTrigger: {
      trigger,
      start: 'top bottom',
      end: 'bottom top',
      scrub: 1.5,
    },
  })
}

/**
 * Texto que se revela con clip-path
 * @param {Element|string} target
 * @param {Object} opts
 */
export function revealText(target, opts = {}) {
  return gsap.from(target, {
    clipPath: 'inset(100% 0% 0% 0%)',
    opacity: 0,
    duration: 1.1,
    ease: 'power4.out',
    ...opts,
  })
}

/**
 * Animación de número contando
 * @param {Element} el - elemento DOM
 * @param {number} end - valor final
 * @param {string} suffix - sufijo (ej: '+', '%')
 */
export function countUp(el, end, suffix = '') {
  const obj = { val: 0 }
  return gsap.to(obj, {
    val: end,
    duration: 2,
    ease: 'power2.out',
    onUpdate: () => {
      el.textContent = Math.round(obj.val).toLocaleString() + suffix
    },
  })
}

export { gsap, ScrollTrigger, Observer }
