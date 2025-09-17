import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'welcome',
      component: () => import('../views/WelcomeView.vue'),
      meta: {
        title: 'Добро пожаловать - Niyat'
      }
    },
    {
      path: '/home',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
      meta: {
        title: 'Главная - Niyat'
      }
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
      meta: {
        title: 'О приложении - Niyat'
      }
    },
    {
      path: '/lottery/:id',
      name: 'lotteryDetails',
      component: () => import('../views/LotteryDetailsView.vue'),
      meta: {
        title: 'Лотерея - Niyat'
      }
    },
    {
      path: '/cart',
      name: 'cart',
      component: () => import('../views/CartView.vue'),
      meta: {
        title: 'Корзина - Niyat'
      }
    },
    {
      path: '/tickets',
      name: 'tickets',
      component: () => import('../views/TicketsView.vue'),
      meta: {
        title: 'Мои билеты - Niyat'
      }
    },
    {
      path: '/tickets/:id',
      name: 'ticketDetails',
      component: () => import('../views/TicketDetailsView.vue'),
      meta: {
        title: 'Билет - Niyat'
      }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: {
        title: 'Профиль - Niyat'
      }
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('../views/NotFoundView.vue'),
      meta: {
        title: 'Страница не найдена - Niyat'
      }
    }
  ],
})

// Обновляем заголовок страницы при навигации
router.beforeEach((to) => {
  if (to.meta?.title) {
    document.title = to.meta.title as string
  }
})

export default router
