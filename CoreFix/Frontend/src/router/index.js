import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/servicios',
    name: 'services',
    component: () => import('../views/ServicesView.vue')
  },
  {
    path: '/trabajos',
    name: 'portfolio',
    component: () => import('../views/WorkPortfolioView.vue')
  },
  {
    path: '/contacto',
    name: 'contact',
    component: () => import('../views/ContactQuoteView.vue')
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

export default router;
