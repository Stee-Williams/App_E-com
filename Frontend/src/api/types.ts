export interface Utilisateur {
  id: number
  email: string
  prenom: string
  nom: string
  telephone?: string
  roles?: string[]
  adresses?: Adresse[]
  dateCreation?: string
}

export interface Categorie {
  id: number
  nom: string
  slug: string
  description?: string
  image?: string
}

export interface ImageProduit {
  id: number
  chemin: string
  ordre: number
}

export interface VarianteProduit {
  id: number
  taille?: string | null
  couleur?: string | null
  stock: number
  actif?: boolean
  libelle?: string
}

export interface Produit {
  id: number
  nom: string
  slug: string
  description?: string
  prix: string
  prixPromo?: string | null
  stock: number
  actif?: boolean
  imagePrincipale?: string
  categorie?: Categorie
  images?: ImageProduit[]
  avis?: Avis[]
  variantes?: VarianteProduit[]
  hasVariantes?: boolean
  stockDisponible?: number
  noteMoyenne?: number | null
  dateCreation?: string
}

export interface Avis {
  id: number
  note: number
  commentaire?: string
  utilisateur?: Utilisateur
  dateCreation?: string
}

export interface Adresse {
  id: number
  libelle: string
  rue: string
  ville: string
  codePostal: string
  pays: string
  parDefaut: boolean
  adresseComplete?: string
}

export interface LigneCommande {
  id: number
  produit: Produit
  quantite: number
  prixUnitaire: string
  sousTotal?: string
  libelleVariante?: string | null
}

export interface BonReduction {
  id: number
  code: string
  type: 'pourcentage' | 'montant_fixe'
  valeur: string
  montantMinimum?: string | null
  dateDebut?: string | null
  dateFin?: string | null
  utilisationsMax?: number | null
  utilisations?: number
  actif?: boolean
}

export interface Commande {
  id: number
  numero: string
  statut: string
  sousTotal: string
  fraisLivraison: string
  reduction: string
  total: string
  lignes: LigneCommande[]
  adresseLivraison?: Adresse
  bonReduction?: BonReduction
  utilisateur?: Utilisateur
  dateCreation?: string
  jetonSuivi?: string
  emailInvite?: string
  prenomInvite?: string
  nomInvite?: string
  nomClient?: string
  adresseLivraisonComplete?: string
}

export interface Facture {
  numero: string
  date?: string
  client: string
  email?: string
  adresse?: string | null
  sousTotal: string
  fraisLivraison: string
  reduction: string
  total: string
  statut: string
  lignes: LigneCommande[]
}

export interface Notification {
  id: number
  type: 'commande' | 'promo'
  titre: string
  message: string
  lien?: string | null
  lu: boolean
  dateCreation?: string
}

export interface NotificationsResponse {
  items: Notification[]
  nonLues: number
}

export interface GuestOrderResponse {
  commande: Commande
  jetonSuivi: string
}

export interface ElementListeSouhaits {
  id: number
  produit: Produit
  dateAjout?: string
}

export interface ArticlePanier {
  produitId: number
  varianteId?: number
  libelleVariante?: string
  nom: string
  prix: string
  image?: string
  quantite: number
  stock: number
}

export function cleArticle(produitId: number, varianteId?: number): string {
  return `${produitId}-${varianteId ?? 0}`
}

export interface AuthResponse {
  jeton: string
  utilisateur: Utilisateur
}

export interface AdminStats {
  utilisateurs: number
  produits: number
  commandes: number
  avis: number
  chiffreAffaires: number
  commandesParMois: { mois: string; total: number }[]
  statutsCommandes: Record<string, number>
}

export interface PaginationMeta {
  page: number
  limit: number
  total: number
  pages: number
}

export interface PaginatedResult<T> {
  items: T[]
  pagination: PaginationMeta
}

/** Montant numérique entier pour panier / API (FCFA sans décimales). */
export function prixEnFcfa(montant: number | string): number {
  const valeur = typeof montant === 'string' ? parseFloat(montant.replace(/\s/g, '')) : montant
  return Number.isNaN(valeur) ? 0 : Math.round(valeur)
}

export function prixEffectif(produit: Produit): number {
  return prixEnFcfa(produit.prixPromo ?? produit.prix)
}

export function formatPrix(montant: number | string): string {
  const valeur = typeof montant === 'string' ? parseFloat(montant.replace(/\s/g, '')) : montant
  if (Number.isNaN(valeur)) {
    return '0 FCFA'
  }

  const entier = Math.round(valeur)
  const signe = entier < 0 ? '-' : ''
  const chiffres = Math.abs(entier).toString()
  const avecSeparateur = chiffres.replace(/\B(?=(\d{3})+(?!\d))/g, '.')

  return `${signe}${avecSeparateur} FCFA`
}

export const STATUTS_COMMANDE: Record<string, string> = {
  en_attente: 'En attente',
  confirmee: 'Confirmée',
  expediee: 'Expédiée',
  livree: 'Livrée',
  annulee: 'Annulée',
}
