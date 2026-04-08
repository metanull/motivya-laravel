# Cas d'utilisation

> Chaque cas d'utilisation est numéroté (UC-C = Coach, UC-A = Athlete, UC-K = Comptable, UC-P = Admin Plateforme) et lié aux **Epics** et **Stories** correspondantes dans GitHub.

## En tant que coach:

**UC-C01** — le site me permet de créer un profil professionnel avec mes spécialités, mon niveau et mes disponibilités.
→ [Epic 2 #52](https://github.com/metanull/motivya-laravel/issues/52) · [E2-S02 #54](https://github.com/metanull/motivya-laravel/issues/54) · [E2-S13 #65](https://github.com/metanull/motivya-laravel/issues/65)

**UC-C02** — le site me permet de définir mes zones d'intervention géographiques (code postal et pays).
→ [Epic 2 #52](https://github.com/metanull/motivya-laravel/issues/52) · [E2-S02 #54](https://github.com/metanull/motivya-laravel/issues/54) · [E2-S13 #65](https://github.com/metanull/motivya-laravel/issues/65)

**UC-C03** — le site me permet de créer une séance avec lieu, date, heure, prix et nombre minimum et maximum de participants. (Une option pourrait être de notifier le prix de l'heure maximum désiré et automatiquement divisé le prix par participants mais cela engendre beaucoup de tracas admin… a garder pour la suite comme option)
→ [Epic 2 #52](https://github.com/metanull/motivya-laravel/issues/52) · [E2-S01 #53](https://github.com/metanull/motivya-laravel/issues/53) · [E2-S03 #55](https://github.com/metanull/motivya-laravel/issues/55) · [E2-S05 #57](https://github.com/metanull/motivya-laravel/issues/57)

**UC-C04** — le site me permet de modifier ou supprimer mes séances « à tout moment » selon les règles définies.
→ [E2-S06 #58](https://github.com/metanull/motivya-laravel/issues/58) · [E2-S07 #59](https://github.com/metanull/motivya-laravel/issues/59)

**UC-C05** — le site me permet de définir un seuil de rentabilité pour chaque séance. (Prix par personnes et end user minimum et maximum)
→ [E2-S03 #55](https://github.com/metanull/motivya-laravel/issues/55) · [E2-S05 #57](https://github.com/metanull/motivya-laravel/issues/57)

**UC-C06** — le site me permet de visualiser en temps réel le nombre de participants inscrits.
→ [E2-S11 #63](https://github.com/metanull/motivya-laravel/issues/63)

**UC-C07** — le site me permet de recevoir une notification lorsque le seuil minimum est atteint. (Envoi de mail et notification vers end user confirmé et coach)
→ [Epic 3 #67](https://github.com/metanull/motivya-laravel/issues/67) · [E3-S22 #89](https://github.com/metanull/motivya-laravel/issues/89) · [E3-S23 #90](https://github.com/metanull/motivya-laravel/issues/90)

**UC-C08** — le site me permet de savoir si une séance est confirmée ou annulée automatiquement. (Tableau de bord pour chaque coach)
→ [E2-S11 #63](https://github.com/metanull/motivya-laravel/issues/63) · [E3-S24 #91](https://github.com/metanull/motivya-laravel/issues/91) · [E3-S25 #92](https://github.com/metanull/motivya-laravel/issues/92)

**UC-C09** — le site me permet de recevoir mes paiements automatiquement après chaque séance ou en fin de mois selon la facilité liée à la plateforme.
→ [Epic 3 #67](https://github.com/metanull/motivya-laravel/issues/67) · [E3-S04 #71](https://github.com/metanull/motivya-laravel/issues/71) · [E3-S11 #78](https://github.com/metanull/motivya-laravel/issues/78) · [Epic 4 #94](https://github.com/metanull/motivya-laravel/issues/94) · [E4-S02 #96](https://github.com/metanull/motivya-laravel/issues/96) · [E4-S14 #110](https://github.com/metanull/motivya-laravel/issues/110)

**UC-C10** — le site me permet de consulter l'historique de mes revenus et paiements.
→ [E2-S12 #64](https://github.com/metanull/motivya-laravel/issues/64) · [E4-S12 #108](https://github.com/metanull/motivya-laravel/issues/108)

**UC-C11** — le site me permet d'envoyer les références de toutes mes activités via WhatsApp ou via Facebook ou autre avec lien pour que les personnes se connectent et uniquement via la plateforme motivya. Je peux donc pousser mes activité programmée sur mes réseaux.
→ [E3-S26 #93](https://github.com/metanull/motivya-laravel/issues/93)

**UC-C12** — le site me permet de voir mes performances (taux de remplissage, revenus par séance, fréquence).
→ [E2-S12 #64](https://github.com/metanull/motivya-laravel/issues/64)

**UC-C13** — le site me permet d'ajuster mes prix en fonction de la demande.
→ [E2-S06 #58](https://github.com/metanull/motivya-laravel/issues/58)

**UC-C14** — le site me permet de gérer mes créneaux récurrents facilement.
→ [E2-S09 #61](https://github.com/metanull/motivya-laravel/issues/61)

**UC-C15** — le site me permet de recevoir des avis et évaluations des clients.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-C16** — Le site se charge de répondre aux avis automatiquement et modéré les posts.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-C17** — le site me permet de synchroniser mon agenda via Ical ou google agenda.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-C18** — le site me permet d'activer ou désactiver mon profil temporairement (notification de vacances ou de remplacement).
→ [E2-S13 #65](https://github.com/metanull/motivya-laravel/issues/65)

**UC-C19** — le site me permet de suivre mes statistiques d'acquisition client.
→ [E2-S12 #64](https://github.com/metanull/motivya-laravel/issues/64)

**UC-C20** — le site m'envoie des recommandation pour pousser mes activités sur les réseaux. (Exemple un cours ne se rempli pas, que faire…)
→ *Non couvert dans les Epics 1–4 (prévu pour Epic 5: Analytics & QA)*

**UC-C21** — Le site me permets d'activé « offre une séance à un amis » ou « bon de réduction à offrir »
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

### Nice to have :

- **UC-C-N01** — le site me suggère des créneaux optimisés selon la demande locale.
- **UC-C-N02** — le site me recommande des prix optimaux selon le marché.
- **UC-C-N03** — le site me propose des zones géographiques à fort potentiel dans ma localité.
- **UC-C-N04** — le site me permet de créer des packs de séances.
- **UC-C-N05** — le site me permet de fidéliser mes clients avec des offres spéciales.
- **UC-C-N06** — le site me permet de voir les tendances sportives locales.

> *Les "Nice to have" Coach ne sont pas encore mappés à des Epics/Stories. Ils feront l'objet d'Epics futurs.*


## En tant que client (END USER)

**UC-A01** — le site me permet de découvrir des séances proches de chez moi.
→ [Epic 3 #67](https://github.com/metanull/motivya-laravel/issues/67) · [E3-S16 #83](https://github.com/metanull/motivya-laravel/issues/83) · [E3-S17 #84](https://github.com/metanull/motivya-laravel/issues/84) · [E3-S18 #85](https://github.com/metanull/motivya-laravel/issues/85)

**UC-A02** — le site me permet de filtrer les séances par sport, niveau, horaire et localisation.
→ [E3-S16 #83](https://github.com/metanull/motivya-laravel/issues/83) · [E3-S17 #84](https://github.com/metanull/motivya-laravel/issues/84)

**UC-A03** — le site me permet de réserver une séance en quelques clics.
→ [E3-S06 #73](https://github.com/metanull/motivya-laravel/issues/73) · [E3-S08 #75](https://github.com/metanull/motivya-laravel/issues/75)

**UC-A04** — le site me permet de payer en ligne de manière sécurisée.
→ [E3-S03 #70](https://github.com/metanull/motivya-laravel/issues/70) · [E3-S07 #74](https://github.com/metanull/motivya-laravel/issues/74) · [E3-S08 #75](https://github.com/metanull/motivya-laravel/issues/75) · [E3-S09 #76](https://github.com/metanull/motivya-laravel/issues/76)

**UC-A05** — le site me permet de voir si une séance est confirmée ou en attente.
→ [E2-S14 #66](https://github.com/metanull/motivya-laravel/issues/66) · [E3-S19 #86](https://github.com/metanull/motivya-laravel/issues/86)

**UC-A06** — le site me permet d'être remboursé automatiquement si la séance est annulée. (Le site permet d'être débité uniquement lors de la confirmation de la séance)
→ [E3-S12 #79](https://github.com/metanull/motivya-laravel/issues/79) · [E3-S13 #80](https://github.com/metanull/motivya-laravel/issues/80) · [E3-S14 #81](https://github.com/metanull/motivya-laravel/issues/81)

**UC-A07** — le site me permet de consulter le profil du coach.
→ [E2-S13 #65](https://github.com/metanull/motivya-laravel/issues/65)

**UC-A08** — le site me permet de voir les avis et notes du coach.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-A09** — le site me permet d'ajouter une séance à mon agenda personnel. (Ical et google agenda)
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-A10** — le site me permet de recevoir des rappels avant la séance.
→ [E3-S21 #88](https://github.com/metanull/motivya-laravel/issues/88)

**UC-A11** — le site me permet d'annuler ma participation selon les conditions définies. (48h en cas de séances confirmée, 24h en cas de séance en attente de confirmation)
→ [E3-S12 #79](https://github.com/metanull/motivya-laravel/issues/79) · [E3-S15 #82](https://github.com/metanull/motivya-laravel/issues/82)

**UC-A12** — le site me permet de consulter mon historique de réservations.
→ [E3-S19 #86](https://github.com/metanull/motivya-laravel/issues/86)

**UC-A13** — Le site me permet d'envoyer ma séance réservée vers mes réseaux et via WhatsApp via un lien copier coller ou un bouton spécifique.
→ [E3-S26 #93](https://github.com/metanull/motivya-laravel/issues/93)

### Nice to have :

- **UC-A-N01** — le site me recommande des séances selon mes préférences et mon historique.
- **UC-A-N02** — le site me propose des séances en fonction de la météo.
- **UC-A-N03** — le site me permet de réserver avec mes amis.
- **UC-A-N04** — le site me permet de suivre ma progression sportive.
- **UC-A-N05** — le site me permet de noter facilement une séance après participation.
- **UC-A-N06** — le site me permet de créer un profil sportif personnalisé.
- **UC-A-N07** — le site me permet de recevoir des notifications de nouvelles séances proches.

> *Les "Nice to have" Athlete ne sont pas encore mappés à des Epics/Stories. Ils feront l'objet d'Epics futurs.*


## En tant que comptable

### Préambule

> Clarifications sur la facturation
> Seul fonctionnement possible: la plateforme affiche les gains et calcule l'abonnement du coach et les commission relative à ses prestations (sur son profil privé). Quand il le souhaite il peut faire sa facture et être payé (sous conditions de temps).
> Une fois la facture payée le montant de son solde est diminué du montant de paiement. On (la plateforme/motivya) ne **peut pas** facturer pour lui et toutes ses facturations **doivent** passer par PEPPOL.
> Le tout doit être détaillé par mois:

#### Exemple:

Jean **freemium**:
- Janvier: 5 séances et 25 paiements 12€ = 300€
  - Frais de paiement: 4,5€
  - A facturer Janvier = 205,5€ TVAC
- Février: 12 séances et 48 paiements de 13€ = 624€
  - Frais de paiement: 9,36€
  - A facturer Février = 427,44€ TVAC (Pourrait passer en abonnement Active)

Marie **Active**: 39€/mois TVAC 20% commission
- Janvier: 5 séances et 25 paiements 12€ = 300€
  - Frais de paiement: 4,5€
  - Abonnement: 39€
  - A facturer Janvier normalement 240 - 4,5 - 39 = 196,5€
  - A facturer Janvier = 205,5€ TVAC (**freemium** plus avantageux pour elle => elle reste en freemium)
- Février: 12 séances et 48 paiements de 13€ = 624€
  - Frais de paiement: 9,36€
  - Abonnement 39€
  - A facturer Février normalement 499,2 - 9,36 - 39 = 450,84€ TVAC (Abonnement **Active** plus avantageux)
  - Si freemium facturer Février = 427,44€ TVAC

Loïc **Premium**: 79€/mois 10% de commission:
- Mars: 16 séances 160 paiements de 11€
  - Frais de paiement 26,4€
  - Abonnement motivya: 79€
  - A facturer Mars 1584 - 26,4 - 79 = 1478,6€ TVAC (**Freemium** = 1232 - 26,4 = 1205,6€ | **Active** = 1408 - 26,4 - 39 = 1342,6€)

#### Fonctionnement comptable TVA:

10 clients paie 10€ pour un cours = 100€ TVAC (82,64 HTVA / Tva de 17,36€) Le coach est en freemium 30% de commission = 30€ TVAC
Le coach doit facturer 70€ TVAC (57,85€ HTVA Tva de 12,15€)

La plateforme garde: 30€ paie 17,36€ de tva et récupère 12,15€ de tva = 30 - 17,36 + 12,15 = **24,79€ reste à motivya**.

Concernant les coach **non assujetti à la TVA** cela soulève une baisse de rentabilité si nous ne faisons pas attention:
Gestion des coachs non assujettis à la TVA (impact et adaptation du modèle Motivya)
Dans le modèle où Motivya encaisse les paiements clients, la TVA est collectée sur le montant total payé par le client. Lorsque le coach est assujetti à la TVA, il facture avec TVA, ce qui permet à Motivya de récupérer une partie de cette TVA. En revanche, lorsqu'un coach est non assujetti (régime de franchise), il facture sans TVA, ce qui empêche Motivya de récupérer cette TVA et réduit fortement la marge.
Concrètement, à revenu client identique, la marge de Motivya est significativement plus faible avec un coach non assujetti si aucun ajustement n'est fait. Il est donc nécessaire d'adapter automatiquement le calcul du paiement coach pour préserver une marge cohérente.
**Le système doit donc fonctionner sur une logique basée sur le montant hors TVA (HTVA), et non sur le montant TVAC**.

Principe de fonctionnement à implémenter :
→ [Epic 4 #94](https://github.com/metanull/motivya-laravel/issues/94) · [E4-S01 #95](https://github.com/metanull/motivya-laravel/issues/95) · [E4-S02 #96](https://github.com/metanull/motivya-laravel/issues/96)

1. Le système calcule le revenu total client en TVAC.
2. Il convertit ce montant en HTVA (TVAC / 1,21).
3. Une marge cible HTVA pour Motivya est définie.
4. Le montant à payer au coach est calculé comme suit :

→ paiement coach = revenu HTVA – marge cible HTVA

Adaptation selon le statut TVA du coach :
- Si le coach est assujetti :
  - il facture avec TVA
  - Motivya récupère la TVA
  - paiement standard possible
- Si le coach est non assujetti :
  - il facture sans TVA
  - aucune TVA récupérable
  - le paiement coach doit être ajusté à la baisse pour préserver la marge (équivalente à la somme HTVA perçue par un coach assujetti)


### En tant que comptable

**UC-K01** — le site me permet de visualiser toutes les transactions effectuées sur la plateforme.
→ [Epic 4 #94](https://github.com/metanull/motivya-laravel/issues/94) · [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105)

**UC-K02** — le site me permet d'automatiser la génération de factures et de note de crédit.
→ [E4-S05a #99](https://github.com/metanull/motivya-laravel/issues/99) · [E4-S05b #100](https://github.com/metanull/motivya-laravel/issues/100) · [E4-S06 #102](https://github.com/metanull/motivya-laravel/issues/102) · [E4-S07 #103](https://github.com/metanull/motivya-laravel/issues/103)

**UC-K03** — le site me permet de suivre les paiements effectués aux coachs.
→ [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105) · [E4-S10 #106](https://github.com/metanull/motivya-laravel/issues/106) · [E4-S12 #108](https://github.com/metanull/motivya-laravel/issues/108)

**UC-K04** — le site me permet de vérifier les commissions prélevées par la plateforme.
→ [E4-S10 #106](https://github.com/metanull/motivya-laravel/issues/106)

**UC-K05** — le site me permet d'exporter les données financières en format compatible (Excel, CSV).
→ [E4-S11 #107](https://github.com/metanull/motivya-laravel/issues/107)

**UC-K06** — le site me permet de générer des rapports financiers mensuels.
→ [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105) · [E4-S11 #107](https://github.com/metanull/motivya-laravel/issues/107)

**UC-K07** — le site me permet de suivre les remboursements clients.
→ [E3-S13 #80](https://github.com/metanull/motivya-laravel/issues/80) · [E4-S07 #103](https://github.com/metanull/motivya-laravel/issues/103) · [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105)

**UC-K08** — le site me permet de contrôler les flux entrants et sortants.
→ [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105)

**UC-K09** — le site me permet d'identifier les anomalies de paiement.
→ [E4-S09 #105](https://github.com/metanull/motivya-laravel/issues/105)

**UC-K10** — le site me permet de gérer la TVA applicable aux transactions.
→ [E4-S01 #95](https://github.com/metanull/motivya-laravel/issues/95)

**UC-K11** — le site me permet de produire des documents comptables automatisés.
→ [E4-S05a #99](https://github.com/metanull/motivya-laravel/issues/99) · [E4-S05b #100](https://github.com/metanull/motivya-laravel/issues/100) · [E4-S05c #101](https://github.com/metanull/motivya-laravel/issues/101) · [E4-S06 #102](https://github.com/metanull/motivya-laravel/issues/102)

(Voir ce qui est déjà dans Stripe pour ne pas faire doublon mais avoir la possibilité de suivre au jour le jour les paiements)

#### Nice to have :

- **UC-K-N01** — le site me permet d'intégrer les données avec un logiciel comptable externe.
- **UC-K-N02** — le site me permet d'automatiser la génération de factures.
- **UC-K-N03** — le site me permet de suivre la rentabilité par coach ou par zone.
- **UC-K-N04** — le site me permet d'anticiper les flux de trésorerie.

> *Les "Nice to have" Comptable ne sont pas encore mappés à des Epics/Stories. Ils feront l'objet d'Epics futurs.*


## En tant que ADMIN (PLATEFORME)

**UC-P01** — le site me permet de valider ou refuser les profils coachs.
→ [Epic 1 #16](https://github.com/metanull/motivya-laravel/issues/16) · [E1-S17 #33](https://github.com/metanull/motivya-laravel/issues/33) · [E1-S18 #34](https://github.com/metanull/motivya-laravel/issues/34) · [E1-S19 #35](https://github.com/metanull/motivya-laravel/issues/35)

**UC-P02** — le site me permet de gérer les comptes utilisateurs (coach et client).
→ [Epic 1 #16](https://github.com/metanull/motivya-laravel/issues/16) · [E1-S15 #31](https://github.com/metanull/motivya-laravel/issues/31)

**UC-P03** — le site me permet de superviser toutes les séances créées sur la plateforme.
→ *Non couvert spécifiquement — la supervision admin des sessions fera l'objet d'un Epic ultérieur*

**UC-P04** — le site me permet de modifier ou supprimer des contenus inappropriés.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-P05** — le site me permet de gérer les commissions appliquées aux coachs.
→ [E4-S08 #104](https://github.com/metanull/motivya-laravel/issues/104) · [E4-S10 #106](https://github.com/metanull/motivya-laravel/issues/106)

**UC-P06** — le site me permet de suivre les performances globales de la plateforme.
→ *Non couvert dans les Epics 1–4 (prévu pour Epic 5: Analytics & QA)*

**UC-P07** — le site me permet d'analyser les zones les plus actives.
→ *Non couvert dans les Epics 1–4 (prévu pour Epic 5: Analytics & QA)*

**UC-P08** — le site me permet de gérer les litiges entre coachs et clients.
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-P09** — le site me permet d'envoyer des communications globales (emails, notifications).
→ *Non couvert dans les Epics 1–4 (prévu pour un Epic ultérieur)*

**UC-P10** — le site me permet de gérer les remboursements exceptionnels.
→ [E3-S13 #80](https://github.com/metanull/motivya-laravel/issues/80) · [E4-S07 #103](https://github.com/metanull/motivya-laravel/issues/103)

**UC-P11** — le site me permet de suivre le taux de conversion (visiteur → client).
→ *Non couvert dans les Epics 1–4 (prévu pour Epic 5: Analytics & QA)*

**UC-P12** — le site me permet de monitorer l'activité en temps réel.
→ *Non couvert dans les Epics 1–4 (prévu pour Epic 5: Analytics & QA)*

### Nice to have :

- **UC-P-N01** — le site me permet de piloter un système de scoring des coachs.
- **UC-P-N02** — le site me permet d'activer des campagnes marketing automatisées.
- **UC-P-N03** — le site me permet d'optimiser la mise en avant des coachs (algorithme).
- **UC-P-N04** — le site me permet de détecter automatiquement les zones à développer.
- **UC-P-N05** — le site me permet de gérer un programme ambassadeur évolutif.
- **UC-P-N06** — le site me permet de tester des modèles de pricing dynamiques. (Voir comptable)

> *Les "Nice to have" Admin ne sont pas encore mappés à des Epics/Stories. Ils feront l'objet d'Epics futurs.*
