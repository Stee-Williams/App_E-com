interface EntreeCache {
  data: unknown
  expire: number
}

const cache = new Map<string, EntreeCache>()
const enCours = new Map<string, Promise<unknown>>()

export function lireCache<T>(cle: string): T | null {
  const entree = cache.get(cle)
  if (!entree) return null
  if (Date.now() > entree.expire) {
    cache.delete(cle)
    return null
  }
  return entree.data as T
}

export function ecrireCache(cle: string, data: unknown, dureeMs: number): void {
  cache.set(cle, { data, expire: Date.now() + dureeMs })
}

export function invaliderCache(prefixe?: string): void {
  if (!prefixe) {
    cache.clear()
    enCours.clear()
    return
  }
  for (const cle of cache.keys()) {
    if (cle.startsWith(prefixe)) cache.delete(cle)
  }
  for (const cle of enCours.keys()) {
    if (cle.startsWith(prefixe)) enCours.delete(cle)
  }
}

export async function avecCache<T>(
  cle: string,
  chargeur: () => Promise<T>,
  dureeMs: number,
): Promise<T> {
  const enMemoire = lireCache<T>(cle)
  if (enMemoire !== null) return enMemoire

  const requete = enCours.get(cle) as Promise<T> | undefined
  if (requete) return requete

  const promesse = chargeur()
    .then((data) => {
      ecrireCache(cle, data, dureeMs)
      return data
    })
    .finally(() => {
      enCours.delete(cle)
    })

  enCours.set(cle, promesse)
  return promesse
}

/** Durées de cache par type de ressource */
export const TTL = {
  categories: 10 * 60 * 1000,
  produits: 2 * 60 * 1000,
  admin: 60 * 1000,
  utilisateur: 60 * 1000,
} as const
