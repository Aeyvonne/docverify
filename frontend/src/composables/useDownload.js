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
   * @param {string} url       — ex: /documents/1/download (chemin relatif uniquement)
   * @param {string} filename  — nom suggéré pour le fichier sauvegardé
   */
  async function download(url, filename) {
    // Bloquer toute URL absolue ou non-relative pour prévenir le SSRF
    if (!url || typeof url !== 'string' || !url.startsWith('/')) {
      downloadError.value = 'URL de téléchargement invalide.'
      return
    }

    // Sanitiser le nom de fichier pour éviter l'injection via link.download
    const safeFilename = filename
      ? String(filename).replace(/[^a-zA-Z0-9._\-\s]/g, '_').slice(0, 200)
      : 'fichier'

    downloading.value  = true
    downloadError.value = null

    try {
      const response = await api.get(url, {
        responseType: 'blob',
      })

      const contentType = response.headers['content-type'] || 'application/octet-stream'
      const blob    = new Blob([response.data], { type: contentType })
      const blobUrl = URL.createObjectURL(blob)

      const link    = document.createElement('a')
      link.href     = blobUrl
      link.download = safeFilename
      document.body.appendChild(link)
      link.click()

      link.remove()
      URL.revokeObjectURL(blobUrl)

    } catch (e) {
      downloadError.value = e.response?.data?.message ?? 'Téléchargement impossible.'
    } finally {
      downloading.value = false
    }
  }

  return { download, downloading, downloadError }
}
