import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

// Импорт страниц
import Home from '@/views/Home.vue'
import Catalog from '@/views/Catalog.vue'
import ItemDetail from '@/views/ItemDetail.vue'
import Results from '@/views/Results.vue'
import Profile from '@/views/Profile.vue'

// Определение маршрутов
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: Home,
    meta: {
      title: 'Главная - NIYAT',
      description: 'Главная страница приложения NIYAT'
    }
  },
  {
    path: '/catalog',
    name: 'Catalog',
    component: Catalog,
    meta: {
      title: 'Каталог - NIYAT',
      description: 'Каталог элементов приложения NIYAT'
    }
  },
  {
    path: '/item/:id',
    name: 'ItemDetail',
    component: ItemDetail,
    props: true,
    meta: {
      title: 'Детали элемента - NIYAT',
      description: 'Подробная информация об элементе'
    }
  },
  {
    path: '/results',
    name: 'Results',
    component: Results,
    meta: {
      title: 'Результаты - NIYAT',
      description: 'Результаты поиска и фильтрации'
    }
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: {
      title: 'Профиль - NIYAT',
      description: 'Профиль пользователя'
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/'
  }
]

// Создание роутера
const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Глобальные хуки навигации
router.beforeEach((to, from, next) => {
  // Обновление заголовка страницы
  if (to.meta?.title) {
    document.title = to.meta.title as string
  }
  
  // Обновление мета-описания
  if (to.meta?.description) {
    const metaDescription = document.querySelector('meta[name="description"]')
    if (metaDescription) {
      metaDescription.setAttribute('content', to.meta.description as string)
    }
  }
  
  next()
})

export default router