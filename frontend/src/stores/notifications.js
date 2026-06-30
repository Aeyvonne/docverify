import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const nonLues       = ref(0)
  let   intervalId    = null

  function getUrls() {
    // Import synchrone depuis sessionStorage pour éviter la dépendance circulaire
    const user = JSON.parse(sessionStorage.getItem('auth_user') || 'null')
    const role = user?.role
    return {
      fetch:    role === 'validateur' ? '/validateur/notifications' : '/admin/notifications',
      markRead: role === 'validateur' ? '/validateur/notifications/mark-read' : '/admin/notifications/mark-read',
    }
  }

  async function fetch() {
    try {
      const { data } = await api.get(getUrls().fetch)
      notifications.value = data.notifications
      nonLues.value       = data.non_lues
    } catch (_) {}
  }

  async function markRead() {
    try {
      await api.patch(getUrls().markRead)
      notifications.value.forEach(n => { n.lu = true })
      nonLues.value = 0
    } catch (_) {}
  }

  function startPolling() {
    fetch()
    intervalId = setInterval(fetch, 30000)
  }

  function stopPolling() {
    if (intervalId) clearInterval(intervalId)
    intervalId = null
  }

  function reset() {
    notifications.value = []
    nonLues.value = 0
    stopPolling()
  }

  return { notifications, nonLues, fetch, markRead, startPolling, stopPolling, reset }
})
