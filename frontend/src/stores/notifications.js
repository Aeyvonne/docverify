import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  const nonLues       = ref(0)
  let   intervalId    = null

  async function fetch() {
    try {
      const { data } = await api.get('/admin/notifications')
      notifications.value = data.notifications
      nonLues.value       = data.non_lues
    } catch (_) {}
  }

  async function markRead() {
    await api.patch('/admin/notifications/mark-read')
    notifications.value.forEach(n => { n.lu = true })
    nonLues.value = 0
  }

  // Polling toutes les 30 secondes
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
