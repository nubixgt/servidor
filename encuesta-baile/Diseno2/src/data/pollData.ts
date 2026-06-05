import { PollStep, PollStats } from "../types";

export const POLL_STEPS: PollStep[] = [
  {
    id: 1,
    titlePrerender: "¿Cómo preferís",
    titleItalic: "el evento?",
    subtitle: "Elegí la opción que más se ajuste a lo que tenés en mente. Tu preferencia define la noche.",
    options: [
      {
        id: "stadium",
        title: "BAILE SOCIAL EN EL ESTADIO",
        imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuALhUMFkEhN6EP_y7bhrFvWAN8KLdk8hhObcZJsHEaEQFkRAj3-W2vkZhU_Rrn2hWnS9dtWYTNsrpQmDq-Ft4XPATTTfGQxdX05zT3AVKkb08WtL82wqSEMbXHthCod6hqM7WkS7jDZHHIx-EjUWbiDhQP3O4IqwzR-Jdin9LFVJOSaAPv7JJUT7PMEaOmZ9WyNxYuXZajz96ZzRdIsimI-5gwL5SARvddJJ8eLo1aZv_bMnwo1Fxad5BjsdGNOUnQh0WZVwSrEW3Ha",
        decorIcon: "stadium",
        highlights: [
          {
            iconName: "MapPin",
            label: "Estadio municipal",
            subLabel: "(se colocará pista de baile)"
          },
          {
            iconName: "Music",
            label: "Música en vivo: Marimba orquesta"
          },
          {
            iconName: "Star",
            label: "Artista internacional"
          }
        ]
      },
      {
        id: "ballroom",
        title: "BAILE EN SALÓN + CONCIERTO",
        imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXu0207HjOnL5QK0J4FSNBKhVKwh4De7HoKSwv3yVwtY2YHEbGlDGQCC34IuZ7IhWnFvan5wSTusU55Bt2gQD1jra-bMbih852biH4d_6VbJjMIFtehhqjF0xr0Z7PA1FiyTN9l1T2wPR2ZXEOVVrh3aTVzrTp-sATBE2wsJod2-Z8Fp9fHn92sJzLmH6jJtX25xczRzCQGjDkMtOPCBUW54YpWkJhUTV6C1skOY-ijs0Z6PznDF7ddYvJHfdOob8qiZeRafYHwmatD3",
        decorIcon: "meeting_room",
        highlights: [
          {
            iconName: "Building",
            label: "Salón de eventos exclusivo"
          },
          {
            iconName: "Sliders",
            label: "Orquesta sinfónica en vivo"
          },
          {
            iconName: "Mic",
            label: "Concierto + Gran Baile"
          }
        ]
      }
    ]
  },
  {
    id: 2,
    titlePrerender: "¿Qué gastronomía",
    titleItalic: "te representa más?",
    subtitle: "Seleccioná el sabor que acompañará tus conversaciones y brindis durante la gala.",
    options: [
      {
        id: "cocktails",
        title: "CÓCTELES & FINGER FOODS",
        imageUrl: "https://images.unsplash.com/photo-1541532713592-79a0317b6b77?auto=format&fit=crop&q=80&w=650&h=650",
        decorIcon: "local_bar",
        highlights: [
          {
            iconName: "Utensils",
            label: "Finger-food creativo gourmet",
            subLabel: "Elegante variedad de bocados y tapas calientes"
          },
          {
            iconName: "Wine",
            label: "Coctelería de autor premium",
            subLabel: "Barra libre ilimitada de tragos de autor"
          },
          {
            iconName: "Users",
            label: "Formato dinámico de socialización de pie"
          }
        ]
      },
      {
        id: "dinner",
        title: "CENA FORMAL DE TRES TIEMPOS",
        imageUrl: "https://images.unsplash.com/photo-1519671482749-fd09be7ccebf?auto=format&fit=crop&q=80&w=650&h=650",
        decorIcon: "restaurant",
        highlights: [
          {
            iconName: "UtensilsCrossed",
            label: "Cena magistral sentados",
            subLabel: "Menú de 3 tiempos formulado por Chef renombrado"
          },
          {
            iconName: "Wine",
            label: "Maridaje selecto de vinos nacionales",
            subLabel: "Copas de vino reserva y champagne de bienvenida"
          },
          {
            iconName: "Grid",
            label: "Asignación formal de mesa numerada"
          }
        ]
      }
    ]
  },
  {
    id: 3,
    titlePrerender: "¿Qué vestimenta",
    titleItalic: "te entusiasma vestir?",
    subtitle: "Tu estilo aporta a la atmósfera única de esta mágica velada de fin de año. Elegí tu preferencia.",
    options: [
      {
        id: "blackTie",
        title: "RIGUROSA ETIQUETA / BLACK TIE",
        imageUrl: "https://images.unsplash.com/photo-1511285560929-80b456fea0bc?auto=format&fit=crop&q=80&w=650&h=650",
        decorIcon: "dry_cleaning",
        highlights: [
          {
            iconName: "Shirt",
            label: "Esmoquin clásico y vestido largo de noche",
            subLabel: "Máxima formalidad y sofisticación tradicional"
          },
          {
            iconName: "Camera",
            label: "Sesión fotográfica en el muro de prensa"
          },
          {
            iconName: "Award",
            label: "Una noche con el glamour de la alfombra roja"
          }
        ]
      },
      {
        id: "contemporary",
        title: "ELEGANCIA CONTEMPORÁNEA",
        imageUrl: "https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?auto=format&fit=crop&q=80&w=650&h=650",
        decorIcon: "sports_martial_arts",
        highlights: [
          {
            iconName: "Sparkles",
            label: "Trajes modernos y vestidos de cóctel chic",
            subLabel: "Estilo sofisticado pero libre y vanguardista"
          },
          {
            iconName: "Activity",
            label: "Comodidad ideal para bailar y disfrutar"
          },
          {
            iconName: "PartyPopper",
            label: "Estética relajada de alta gala sin rigidez"
          }
        ]
      }
    ]
  }
];

export const INITIAL_STATS: PollStats = {
  format: {
    stadium: 48,
    ballroom: 94
  },
  gastronomy: {
    cocktails: 62,
    dinner: 80
  },
  dressCode: {
    blackTie: 73,
    contemporary: 69
  }
};
