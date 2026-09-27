export const CONTACT = {
  companyName: 'Las Orquídeas, S.A.',
  phoneDisplay: '2427 3778',
  whatsappNumber: '50224273778',
  salesOffice: 'Local 21A, CC Plaza Real, Chimaltenango',
};

export const whatsappLink = (message) =>
  `https://wa.me/${CONTACT.whatsappNumber}?text=${encodeURIComponent(message)}`;
