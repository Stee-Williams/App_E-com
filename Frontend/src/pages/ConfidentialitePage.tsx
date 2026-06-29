import { PageTitle } from '../components/Ui'

export default function ConfidentialitePage() {
  return (
    <div className="prose max-w-3xl dark:prose-invert">
      <PageTitle title="Politique de confidentialité" />
      <p>NovaShop s'engage à protéger vos données personnelles conformément au RGPD.</p>
      <h2>Données collectées</h2>
      <p>Nous collectons uniquement les informations nécessaires à la création de compte, la livraison et le suivi des commandes.</p>
      <h2>Vos droits</h2>
      <p>Vous pouvez demander l'accès, la modification ou la suppression de vos données en nous contactant.</p>
    </div>
  )
}
