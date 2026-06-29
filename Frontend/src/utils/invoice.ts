import type { Facture } from '../api/types'
import { STATUTS_COMMANDE, formatPrix } from '../api/types'

function ligneFacture(ligne: Facture['lignes'][number]): string {
  const variante = ligne.libelleVariante ? ` (${ligne.libelleVariante})` : ''
  return `<tr>
    <td>${ligne.quantite}x ${ligne.produit?.nom ?? 'Produit'}${variante}</td>
    <td style="text-align:right">${formatPrix(ligne.prixUnitaire)}</td>
    <td style="text-align:right">${formatPrix(ligne.sousTotal ?? parseFloat(ligne.prixUnitaire) * ligne.quantite)}</td>
  </tr>`
}

function htmlFacture(facture: Facture): string {
  const lignes = facture.lignes.map(ligneFacture).join('')

  return `<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Facture ${facture.numero}</title>
  <style>
    body { font-family: system-ui, sans-serif; color: #1e293b; margin: 40px; }
    h1 { color: #4f46e5; margin-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 24px; }
    th, td { border-bottom: 1px solid #e2e8f0; padding: 10px 8px; text-align: left; }
    th { background: #f8fafc; }
    .totaux { margin-top: 24px; max-width: 320px; margin-left: auto; }
    .totaux div { display: flex; justify-content: space-between; padding: 6px 0; }
    .total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #4f46e5; padding-top: 10px; }
    @media print { body { margin: 20px; } }
  </style>
</head>
<body>
  <h1>NovaShop</h1>
  <p>Facture <strong>${facture.numero}</strong> — ${facture.date ?? ''}</p>
  <p><strong>${facture.client}</strong>${facture.email ? `<br/>${facture.email}` : ''}</p>
  ${facture.adresse ? `<p>${facture.adresse}</p>` : ''}
  <p>Statut : ${STATUTS_COMMANDE[facture.statut] ?? facture.statut}</p>
  <table>
    <thead><tr><th>Article</th><th style="text-align:right">Prix unit.</th><th style="text-align:right">Sous-total</th></tr></thead>
    <tbody>${lignes}</tbody>
  </table>
  <div class="totaux">
    <div><span>Sous-total</span><span>${formatPrix(facture.sousTotal)}</span></div>
    <div><span>Livraison</span><span>${formatPrix(facture.fraisLivraison)}</span></div>
    ${parseFloat(facture.reduction) > 0 ? `<div><span>Réduction</span><span>-${formatPrix(facture.reduction)}</span></div>` : ''}
    <div class="total"><span>Total</span><span>${formatPrix(facture.total)}</span></div>
  </div>
</body>
</html>`
}

export function telechargerFacturePdf(facture: Facture): void {
  const html = htmlFacture(facture)
  const fenetre = window.open('', '_blank')
  if (!fenetre) {
    const blob = new Blob([html], { type: 'text/html' })
    const url = URL.createObjectURL(blob)
    const lien = document.createElement('a')
    lien.href = url
    lien.download = `facture-${facture.numero}.html`
    lien.click()
    URL.revokeObjectURL(url)
    return
  }
  fenetre.document.write(html)
  fenetre.document.close()
  fenetre.focus()
  setTimeout(() => fenetre.print(), 300)
}
