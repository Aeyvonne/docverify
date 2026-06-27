import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api/axios'

export const useDocumentsStore = defineStore('documents', () => {
  const list    = ref([])
  const loading = ref(false)
  const loaded  = ref(false)  // évite de recharger si déjà en cache

  async function fetchAll(force = false) {
    if (loaded.value && !force) return
    loading.value = true
    try {
      const { data } = await api.get('/documents')
      list.value  = data
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  /** Révoque un document localement après appel API */
  async function revoke(id, motif) {
    const { data } = await api.patch(`/documents/${id}/revoke`, {
      motif_revocation: motif,
    })
    const idx = list.value.findIndex(d => d.id === id)
    if (idx !== -1) list.value[idx] = data.document
    return data
  }

  /** Ajoute un document en tête de liste après création */
  function prepend(doc) {
    list.value.unshift(doc)
  }

  /** Remet le cache à zéro (utile après logout) */
  function reset() {
    list.value  = []
    loaded.value = false
  }

  return { list, loading, loaded, fetchAll, revoke, prepend, reset }
})
