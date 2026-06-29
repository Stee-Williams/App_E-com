import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { apiGetCached } from '../api/client'
import { TTL, invaliderCache } from '../api/cache'
import type { Categorie } from '../api/types'

interface CategoriesContextValue {
  categories: Categorie[]
  chargement: boolean
  ajouterCategorie: (categorie: Categorie) => void
  rafraichir: () => Promise<Categorie[]>
}

const CategoriesContext = createContext<CategoriesContextValue | null>(null)

export function CategoriesProvider({ children }: { children: ReactNode }) {
  const [categories, setCategories] = useState<Categorie[]>([])
  const [chargement, setChargement] = useState(true)

  const rafraichir = useCallback(async (forcer = false) => {
    if (forcer) invaliderCache('GET:/api/categories')
    const data = await apiGetCached<Categorie[]>('/api/categories', TTL.categories)
    setCategories(data)
    return data
  }, [])

  useEffect(() => {
    rafraichir().finally(() => setChargement(false))
  }, [rafraichir])

  const ajouterCategorie = useCallback((categorie: Categorie) => {
    setCategories((prev) => [...prev, categorie].sort((a, b) => a.nom.localeCompare(b.nom)))
    invaliderCache('GET:/api/categories')
  }, [])

  const value = useMemo(
    () => ({ categories, chargement, ajouterCategorie, rafraichir: () => rafraichir(true) }),
    [categories, chargement, ajouterCategorie, rafraichir],
  )

  return <CategoriesContext.Provider value={value}>{children}</CategoriesContext.Provider>
}

export function useCategories() {
  const ctx = useContext(CategoriesContext)
  if (!ctx) throw new Error('useCategories doit être utilisé dans CategoriesProvider')
  return ctx
}

export function invaliderCacheProduits(): void {
  invaliderCache('GET:/api/products')
  invaliderCache('GET:/api/admin/products')
}
