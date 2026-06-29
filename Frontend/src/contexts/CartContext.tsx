import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import type { ArticlePanier } from '../api/types'
import { cleArticle } from '../api/types'

interface CartContextValue {
  articles: ArticlePanier[]
  ajouter: (article: Omit<ArticlePanier, 'quantite'> & { quantite?: number }) => void
  retirer: (produitId: number, varianteId?: number) => void
  modifierQuantite: (produitId: number, quantite: number, varianteId?: number) => void
  vider: () => void
  totalArticles: number
  sousTotal: number
}

const CartContext = createContext<CartContextValue | null>(null)
const STORAGE_KEY = 'novashop_cart'

function lirePanier(): ArticlePanier[] {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  } catch {
    return []
  }
}

function memeArticle(a: ArticlePanier, produitId: number, varianteId?: number): boolean {
  return a.produitId === produitId && (a.varianteId ?? 0) === (varianteId ?? 0)
}

export function CartProvider({ children }: { children: ReactNode }) {
  const [articles, setArticles] = useState<ArticlePanier[]>(lirePanier)

  useEffect(() => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(articles))
  }, [articles])

  const ajouter = (article: Omit<ArticlePanier, 'quantite'> & { quantite?: number }) => {
    setArticles((prev) => {
      const existant = prev.find((a) => memeArticle(a, article.produitId, article.varianteId))
      if (existant) {
        return prev.map((a) =>
          memeArticle(a, article.produitId, article.varianteId)
            ? { ...a, quantite: Math.min(a.stock, a.quantite + (article.quantite ?? 1)) }
            : a,
        )
      }
      return [...prev, { ...article, quantite: article.quantite ?? 1 }]
    })
  }

  const retirer = (produitId: number, varianteId?: number) => {
    setArticles((prev) => prev.filter((a) => !memeArticle(a, produitId, varianteId)))
  }

  const modifierQuantite = (produitId: number, quantite: number, varianteId?: number) => {
    if (quantite <= 0) {
      retirer(produitId, varianteId)
      return
    }
    setArticles((prev) =>
      prev.map((a) =>
        memeArticle(a, produitId, varianteId) ? { ...a, quantite: Math.min(a.stock, quantite) } : a,
      ),
    )
  }

  const vider = () => setArticles([])

  const totalArticles = articles.reduce((sum, a) => sum + a.quantite, 0)
  const sousTotal = articles.reduce((sum, a) => sum + parseFloat(a.prix) * a.quantite, 0)

  const value = useMemo(
    () => ({ articles, ajouter, retirer, modifierQuantite, vider, totalArticles, sousTotal }),
    [articles, totalArticles, sousTotal],
  )

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}

export function useCart() {
  const ctx = useContext(CartContext)
  if (!ctx) throw new Error('useCart doit être utilisé dans CartProvider')
  return ctx
}

export { cleArticle }
