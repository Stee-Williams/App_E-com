import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Minus, Plus, Trash2 } from 'lucide-react'
import { api, imageUrl } from '../api/client'
import { cleArticle, formatPrix, type GuestOrderResponse } from '../api/types'
import { useAuth } from '../contexts/AuthContext'
import { useCart } from '../contexts/CartContext'
import { invaliderCacheProduits } from '../contexts/CategoriesContext'
import { FormField, Input } from '../components/Form'
import { EmptyState, PageTitle } from '../components/Ui'

const FRAIS_LIVRAISON = 2500

export default function PanierPage() {
  const { articles, modifierQuantite, retirer, vider, sousTotal } = useCart()
  const { estConnecte } = useAuth()
  const navigate = useNavigate()
  const [codeBon, setCodeBon] = useState('')
  const [reduction, setReduction] = useState(0)
  const [chargement, setChargement] = useState(false)
  const [erreur, setErreur] = useState('')
  const [adresseId, setAdresseId] = useState<number | ''>('')
  const [modeInvite, setModeInvite] = useState(false)
  const [invite, setInvite] = useState({
    email: '',
    prenom: '',
    nom: '',
    telephone: '',
    rue: '',
    ville: '',
    codePostal: '',
    pays: 'Sénégal',
  })
  const [confirmation, setConfirmation] = useState<{ numero: string; jeton: string } | null>(null)

  const validerBon = async () => {
    try {
      const res = await api<{ reduction: number }>('/api/coupons/validate', {
        method: 'POST',
        body: JSON.stringify({ code: codeBon, montant: sousTotal }),
      })
      setReduction(res.reduction)
      setErreur('')
    } catch (e) {
      setReduction(0)
      setErreur(e instanceof Error ? e.message : 'Code invalide')
    }
  }

  const payloadArticles = () =>
    articles.map((a) => ({
      produitId: a.produitId,
      quantite: a.quantite,
      ...(a.varianteId ? { varianteId: a.varianteId } : {}),
    }))

  const commanderConnecte = async () => {
    await api('/api/orders', {
      method: 'POST',
      body: JSON.stringify({
        articles: payloadArticles(),
        adresseId: adresseId || undefined,
        codeBon: codeBon || undefined,
      }),
    })
    invaliderCacheProduits()
    vider()
    navigate('/commandes')
  }

  const commanderInvite = async () => {
    const data = await api<GuestOrderResponse>('/api/orders/guest', {
      method: 'POST',
      body: JSON.stringify({
        articles: payloadArticles(),
        codeBon: codeBon || undefined,
        email: invite.email,
        prenom: invite.prenom,
        nom: invite.nom,
        telephone: invite.telephone || undefined,
        adresse: {
          libelle: 'Livraison',
          rue: invite.rue,
          ville: invite.ville,
          codePostal: invite.codePostal,
          pays: invite.pays,
        },
      }),
    })
    invaliderCacheProduits()
    vider()
    setConfirmation({ numero: data.commande.numero, jeton: data.jetonSuivi })
    localStorage.setItem('novashop_last_jeton', data.jetonSuivi)
  }

  const commander = async () => {
    if (!estConnecte && !modeInvite) {
      setModeInvite(true)
      return
    }
    setChargement(true)
    setErreur('')
    try {
      if (estConnecte) {
        await commanderConnecte()
      } else {
        await commanderInvite()
      }
    } catch (e) {
      setErreur(e instanceof Error ? e.message : 'Erreur lors de la commande')
    } finally {
      setChargement(false)
    }
  }

  const total = sousTotal + FRAIS_LIVRAISON - reduction

  if (confirmation) {
    return (
      <div className="mx-auto max-w-lg text-center">
        <PageTitle title="Commande confirmée !" />
        <div className="card-hover p-8">
          <p className="text-lg">Merci {invite.prenom} !</p>
          <p className="mt-2 text-muted">Votre commande <strong>{confirmation.numero}</strong> a été enregistrée.</p>
          <p className="mt-4 rounded-xl bg-surface-muted p-4 text-sm">
            Conservez ce jeton de suivi :<br />
            <code className="mt-2 block break-all font-mono text-brand-600">{confirmation.jeton}</code>
          </p>
          <Link to={`/suivi-commande?jeton=${confirmation.jeton}`} className="btn-primary mt-6 inline-flex">
            Suivre ma commande
          </Link>
        </div>
      </div>
    )
  }

  if (articles.length === 0) {
    return (
      <div>
        <PageTitle title="Mon panier" />
        <EmptyState
          message="Votre panier est vide."
          action={<Link to="/produits" className="btn-primary">Découvrir les produits</Link>}
        />
      </div>
    )
  }

  return (
    <div>
      <PageTitle title="Mon panier" subtitle={`${articles.length} article(s)`} />

      <div className="grid gap-8 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          {articles.map((article) => (
            <div key={cleArticle(article.produitId, article.varianteId)} className="card-hover flex gap-4 p-4">
              <img src={imageUrl(article.image, article.produitId)} alt={article.nom} className="h-24 w-24 rounded-xl object-cover" />
              <div className="flex flex-1 flex-col justify-between">
                <div>
                  <h3 className="font-semibold">{article.nom}</h3>
                  {article.libelleVariante && <p className="text-xs text-muted">{article.libelleVariante}</p>}
                  <p className="text-price">{formatPrix(article.prix)}</p>
                </div>
                <div className="flex items-center gap-3">
                  <button type="button" onClick={() => modifierQuantite(article.produitId, article.quantite - 1, article.varianteId)} className="rounded-lg border p-1">
                    <Minus size={14} />
                  </button>
                  <span>{article.quantite}</span>
                  <button type="button" onClick={() => modifierQuantite(article.produitId, article.quantite + 1, article.varianteId)} className="rounded-lg border p-1">
                    <Plus size={14} />
                  </button>
                  <button type="button" onClick={() => retirer(article.produitId, article.varianteId)} className="ml-auto text-red-500">
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        <div className="card-hover h-fit p-6">
          <h2 className="text-lg font-bold">Récapitulatif</h2>
          <div className="mt-4 space-y-2 text-sm">
            <div className="flex justify-between"><span>Sous-total</span><span>{formatPrix(sousTotal)}</span></div>
            <div className="flex justify-between"><span>Livraison</span><span>{formatPrix(FRAIS_LIVRAISON)}</span></div>
            {reduction > 0 && (
              <div className="flex justify-between text-green-600"><span>Réduction</span><span>-{formatPrix(reduction)}</span></div>
            )}
            <div className="flex justify-between border-t pt-2 text-lg font-bold">
              <span>Total</span><span>{formatPrix(total)}</span>
            </div>
          </div>

          <FormField label="Code promo" className="mt-4">
            <div className="flex gap-2">
              <Input placeholder="Ex. BIENVENUE10" value={codeBon} onChange={(e) => setCodeBon(e.target.value)} />
              <button type="button" onClick={validerBon} className="btn-secondary shrink-0">Appliquer</button>
            </div>
          </FormField>

          {estConnecte && (
            <FormField label="Adresse de livraison" hint="Indiquez l'ID depuis votre profil, ou laissez vide" className="mt-4">
              <Input
                type="number"
                min={1}
                placeholder="N° d'adresse"
                value={adresseId}
                onChange={(e) => setAdresseId(e.target.value ? Number(e.target.value) : '')}
              />
            </FormField>
          )}

          {(modeInvite || !estConnecte) && !estConnecte && (
            <div className="mt-4 space-y-3 border-t border-border pt-4">
              <p className="text-sm font-semibold">Commander en tant qu'invité</p>
              <div className="grid gap-3 sm:grid-cols-2">
                <FormField label="Prénom"><Input value={invite.prenom} onChange={(e) => setInvite({ ...invite, prenom: e.target.value })} /></FormField>
                <FormField label="Nom"><Input value={invite.nom} onChange={(e) => setInvite({ ...invite, nom: e.target.value })} /></FormField>
              </div>
              <FormField label="Email"><Input type="email" value={invite.email} onChange={(e) => setInvite({ ...invite, email: e.target.value })} /></FormField>
              <FormField label="Téléphone"><Input value={invite.telephone} onChange={(e) => setInvite({ ...invite, telephone: e.target.value })} /></FormField>
              <FormField label="Rue"><Input value={invite.rue} onChange={(e) => setInvite({ ...invite, rue: e.target.value })} /></FormField>
              <div className="grid gap-3 sm:grid-cols-2">
                <FormField label="Ville"><Input value={invite.ville} onChange={(e) => setInvite({ ...invite, ville: e.target.value })} /></FormField>
                <FormField label="Code postal"><Input value={invite.codePostal} onChange={(e) => setInvite({ ...invite, codePostal: e.target.value })} /></FormField>
              </div>
              <FormField label="Pays"><Input value={invite.pays} onChange={(e) => setInvite({ ...invite, pays: e.target.value })} /></FormField>
            </div>
          )}

          {erreur && <p className="mt-2 text-sm text-red-500">{erreur}</p>}

          <button type="button" onClick={commander} className="btn-primary mt-6 w-full" disabled={chargement}>
            {chargement ? 'Commande en cours...' : estConnecte ? 'Passer la commande' : modeInvite ? 'Confirmer la commande invité' : 'Commander sans compte'}
          </button>

          {!estConnecte && !modeInvite && (
            <p className="mt-2 text-center text-xs text-muted">
              ou <Link to="/connexion" className="link-accent">connectez-vous</Link> si vous avez un compte
            </p>
          )}

          <p className="mt-2 text-center text-xs text-muted">Paiement non inclus — commande enregistrée sans paiement en ligne</p>
        </div>
      </div>
    </div>
  )
}
