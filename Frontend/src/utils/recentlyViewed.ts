import type { Produit } from '../api/types'

const STORAGE_KEY = 'novashop_recent'
const MAX_ITEMS = 8

export function lireRecents(): number[] {
  try {
    const data = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(data) ? data.filter((id) => typeof id === 'number') : []
  } catch {
    return []
  }
}

export function ajouterRecent(produitId: number): void {
  const ids = lireRecents().filter((id) => id !== produitId)
  ids.unshift(produitId)
  localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.slice(0, MAX_ITEMS)))
}

export async function chargerRecents(
  fetcher: (path: string) => Promise<Produit>,
): Promise<Produit[]> {
  const ids = lireRecents()
  const produits: Produit[] = []

  for (const id of ids) {
    try {
      produits.push(await fetcher(`/api/products/${id}`))
    } catch {
      // produit supprimé ou indisponible
    }
  }

  return produits
}
