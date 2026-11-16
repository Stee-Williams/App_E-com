import { useEffect, useState } from 'react'
import { Link, useLocation, useSearchParams } from 'react-router-dom'
import { api, apiGet } from '../api/client'
import type { PaymentStatusResponse } from '../api/types'
import { useCart } from '../contexts/CartContext'
import { PageTitle } from '../components/Ui'

export default function PaiementPage() {
  const { pathname } = useLocation()
  const [searchParams] = useSearchParams()
  const { vider } = useCart()
  const [message, setMessage] = useState('Vérification du paiement...')
  const [numero, setNumero] = useState('')
  const [erreur, setErreur] = useState('')
  const annulation = pathname.endsWith('/annule')
  const [commandeInvite, setCommandeInvite] = useState(false)

  useEffect(() => {
    let active = true
    const sessionId = searchParams.get('session_id')
    const jeton = searchParams.get('jeton')

    const afficherPaiementReussi = (statut: PaymentStatusResponse) => {
      if (!active) return
      vider()
      setNumero(statut.numero)
      setCommandeInvite(statut.invite)
      setMessage('Paiement confirmé. Votre commande est enregistrée.')
    }

    const verifier = async () => {
      try {
        if (annulation) {
          if (!jeton) throw new Error('Le jeton de commande est manquant.')
          const statut = await api<PaymentStatusResponse>('/api/payments/cancel', {
            method: 'POST',
            body: JSON.stringify({ jeton }),
          })
          if (statut.statutPaiement === 'paye') {
            afficherPaiementReussi(statut)
          } else if (active) {
            setNumero(statut.numero)
            setCommandeInvite(statut.invite)
            setMessage(statut.statutPaiement === 'annule'
              ? 'Paiement annulé. Votre panier est conservé.'
              : 'Le paiement est en cours de vérification. Votre panier est conservé.')
          }
          return
        }

        if (!sessionId) throw new Error('La référence du paiement est manquante.')
        for (let tentative = 0; tentative < 10 && active; tentative += 1) {
          const statut = await apiGet<PaymentStatusResponse>(
            `/api/payments/status?session_id=${encodeURIComponent(sessionId)}`,
          )
          if (statut.statutPaiement === 'paye') {
            afficherPaiementReussi(statut)
            return
          }
          if (statut.statutPaiement === 'annule') {
            setMessage('Le paiement n’a pas abouti. Votre panier est conservé.')
            return
          }
          await new Promise((resolve) => window.setTimeout(resolve, 1500))
        }
        if (active) setMessage('Le paiement est toujours en cours de confirmation. Actualisez cette page dans quelques instants.')
      } catch (e) {
        if (active) {
          setErreur(e instanceof Error ? e.message : 'Impossible de vérifier le paiement.')
          setMessage('')
        }
      }
    }

    void verifier()

    return () => {
      active = false
    }
  }, [annulation, searchParams, vider])

  return (
    <div className="mx-auto max-w-lg text-center">
      <PageTitle title={annulation ? 'Retour au panier' : 'Confirmation du paiement'} />
      <div className="card-hover p-8">
        {message && <p className="text-lg">{message}</p>}
        {erreur && <p className="text-sm text-red-500">{erreur}</p>}
        {numero && <p className="mt-3 text-muted">Commande <strong>{numero}</strong></p>}
        <div className="mt-6 flex justify-center gap-3">
          {numero && message.startsWith('Paiement confirmé') ? (
            <Link
              to={commandeInvite ? `/suivi-commande?jeton=${encodeURIComponent(searchParams.get('jeton') ?? '')}` : '/commandes'}
              className="btn-primary"
            >
              {commandeInvite ? 'Suivre ma commande' : 'Voir mes commandes'}
            </Link>
          ) : (
            <Link to="/panier" className="btn-primary">Retour au panier</Link>
          )}
          {erreur && <button type="button" className="btn-secondary" onClick={() => window.location.reload()}>Réessayer</button>}
        </div>
      </div>
    </div>
  )
}
