/**
 * Composable de téléchargement de fichiers via axios.
 * Résout le problème d'authentification Sanctum :
 * un <a href> simple ne transmet pas le Bearer token,
 * donc le backend retourne 401. On passe par axios qui
 * injecte automatiquement le header Authorization.
 */
import { ref } from 'vue'
import api from '@/api/axios'

export function useDownload() {
  const downloading = ref(false)
  const downloadError = ref(null)

  /**
   * Télécharge un fichier depuis une URL protégée.
   * @param {string} url       — ex: /documents/1/download
   * @param {string} filename  — nom suggéré pour le fichier sauvegardé
   */
  async function download(url, filename) {
    downloading.value  = true
    downloadError.value = null

    try {
      const response = await api.get(url, {
        responseType: 'blob', // important : réponse binaire
      })

      // Créer un lien temporaire pointant vers le blob
      const blob    = new Blob([response.data], { type: 'application/pdf' })
      const blobUrl = URL.createObjectURL(blob)

      const link    = document.createElement('a')
      link.href     = blobUrl
      link.download = filename
      document.body.appendChild(link)
      link.click()

      // Nettoyage immédiat
      link.remove()
      URL.revokeObjectURL(blobUrl)

    } catch (e) {
      downloadError.value = e.response?.data?.message ?? 'Téléchargement impossible.'
      console.error('[useDownload]', e)
    } finally {
      downloading.value = false
    }
  }

  return { download, downloading, downloadError }
}
