import { defineStore } from 'pinia';

const INITIAL_MODELS = [
  {
    id: 'M01',
    name: 'Julian Saint',
    look: 'Midnight Velvet Tuxedo',
    designer: 'Maison Ariste',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDcim2fUkBTecinQr78zQDmgM4fZ-HuWi7V8Yj0vGbTFDSc2ha4iWkEqsF6F8BOhmZoAaHBFu7jIdRQjpsRhwWA527thwfqBVpVR7JvWTG_N8ZPrWQVmC1wvNrfioMNCOxDdni28yTS9to8woebhSfv-u96unZAbC6Rg1d_jt47jY0XWanbb7lplj8cNreJz0YN9ymHOZ-Q61nFFzZV_hlaHL93hYBcnYObqq4fIyZMDWTyHJYMWsB8',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDeyInIOg_lbjDomti6UUYCwZIoR5R3jT89R7dMJDJhG9-JoxPqSKLwrN5XutdOFnTAunaR-iGxz6JaA4YmwJtcGgleKsw9h9YfUEvMBY99hKzv7_WzsWPV551VqOsGWC2riVx5jxzMaBCF4IEC2V7he3TS3mtyGTY8DtZcPsp605UG9gBbGp0BmBKuKidrrIPv1RN8Ksqyatxwz5FmdUMShPDXGC8MJr4Sb_GXY2M6u0AB65MfP7BF',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA4GYhi_fwFdRjQnQpVLb-_is7n84QaBa60O_4HKaomEm4nBBYFr_S4a4VX5tx9yt7z8BZ1LO5J1ch94GHfDXg6cPbFqfu651ZeWqPHUF5B2lLAH85iBnSav1up0QRJ9VmbzH8PfowYnkZnXgbVvb6Ic6f-Ea1ty7l9lyD3USCw1TI-PHvMPP586NSTDpMc4_ilXp6QKj_M_SFOlshIluyaQ-hlsWR3kEqgWSc-ZlnfWTG7O7b_lq_E',
    status: 'JUDGED',
    scores: { walk: 9.9, presence: 10.0, garment: 9.9, originality: 9.9, total: 9.94 }
  },
  {
    id: 'M02',
    name: 'Elara Vance',
    look: 'Silken Drapes in Onyx',
    designer: 'Aura Ateliers',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDabBlRBbk81V-1iprPlWXY1OfV5CXu8t6GoYD26eyAwmKFR4PYDNTlQoC1goJkfy51WLiYMelwqyOAVBpgzNJ6DXx7xC9MuUDrUVS5u4m3qaJ97L75Az1t02fXw1ytwVoMVh81vyZHCT0kXwZymH38D9z-Kt2_BqI_oMRhY4U62gBeuVvs9kJQTsVMucS9e2noFJ7kWRG5hCl1yrxQYc4h0hYuoROAYDX7N3I3xq4vi4Zuc7MT0DGq',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBLiVebZXHR4M81C_J7OZyAeZlz0kNSYfU3GaQTJou3ADPdMz9viB0Sh7b6XHclAYNnHkt6JSaTfZTK8N0GGQ74Pd-cR49BZfO1ujNyxRN1HzT6gXErm3gqkC5gHF7UzVW94BGKnbZPpePjBuDeV-LO39GQNfMQxY5zYr28waRdRJNlz4s-B9aGWoufsOgvogh1h4Ws-hmYxEOyp_1vtUw5pjUNF6xUB8BBoK5E1UdZ7gWGvzmslgOS',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDOQqe-mZc6BqMzOv4xDwZDSuryFha7FO52XAHdRDXHHjAv3eRgMa5LItZz-YV9XcH3visBQl4zjwaJvCFcyPo3UD-KO3FgSket-bMUw-ezREh6U0dvR_L6YSIlUBQqCG5o5kSlrQUhZtSneV_szCbDhtmee7Vg3j9uAr05_Up7c1z0EhMc3qrPsSr-BN8klxqyC75WRswmRk4Rh2XlDi6Rzwwc4kgLZaVccw4wwutIq-hcrPbD3C6F',
    status: 'WALKING',
    scores: { walk: 9.8, presence: 9.7, garment: 9.8, originality: 9.8, total: 9.78 }
  },
  {
    id: 'M03',
    name: 'Naomi Chen',
    look: 'Origami Pleated Sculpture',
    designer: 'Sora Studios',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAnNtJmJuhFXD9k8OrPNJx2_nwSUoU1LPgyTKdGdnL1J70QPKDpYm9RM9TrkJZo3htslf_FOY923tgnaiOjpOTGopGuBqSQ_K-ah0zSpEzJl3-sKwafJyHUp8XKaQ2CKY0_bElDrPMuBOzpEJUHnhY-vPYjv7vQpbAyvXp7d7nzOsNkUUCuYkTX0CF_2DoQz5t-pRQsaeRj1qfs6iW8bBgRUMbnFpjH4kl7Pf9clyFGpzPr2cdJk5gb',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBdpZK1HCEkbOuDWmUWIBAhX_sASm8UrJH86yQ3xvbmhq3YGbDEFI-4XkVRiSHiZ52Jz2_rs8tT8hynz5GoNmWqbPwsa-MJXW9UAfXmUlfFabXlC_4XS5lbwavLOQEhJnwLN5O7lutoNDCN81X1Uya4WTzQlFwKs5Qgng5GNmRsKnUDBdel_LXWE9rVR5oH1IfMoUDx94MD6ofnYqMxs6tIZB1q6_pP1box2HXxDqcTnMKWcwL_d7Js',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB90OKR67OfrYaVfxiZ_Bog_x-UAx3O1-7t29J3mlVECKA7G7TtzKTmNBI1XUvZ7Pmiw6At1o_SiegPTnJNBlV173ZoLr3d_pH3qLJpQkrJc4xYY0VIS_kfozBxfXodZrRCdR_RpHw3OZRxS7jxZhJtonPyr2YKLts6ZYzVvnMlmFWjA_IQVONemElRPlbpFouLLAchYyt96HyYTCOXGZUMIFGgOx-iISrsW49wAbg30LPZGLtBY-5f',
    status: 'JUDGED',
    scores: { walk: 9.6, presence: 9.6, garment: 9.7, originality: 9.6, total: 9.62 }
  },
  {
    id: 'M04',
    name: 'Marcus Thorne',
    look: 'Deconstructed Linen Suit',
    designer: 'Kerr & Co',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCz7mTo7bIDZUvOvxhFqt8kRBCxCcRj5QITpH1QI8CnwPQdZ5uCJp0hsJcMRgQHuixD3XL_3sjmyclYgvzWwbryP4w7nRLj2PuP-cpwYhUXjlAC0ejABEtHrt7piSVUAnLLeX1RuVLtCPvyXHyLOoXwBSkRfvf08nSjnRSdTmuJShVXtYGk0AGD0UmrrRM6C4DNWVimUBtjwKJm64KBZCd4lupM-8jd7FSTx-gNFm0SVzZQcq5recoq',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAfaLcj0AaS-RYmD02RX7Rgw4cdvYN-Te4txhMEnon7o165KFlx7305UPfag0QiOQ7-uzE42LuIeBjgnzpv_EjH8oeYEjD_ccTbfnf4Awz2k71EqZmWg8FpQnkqM3lOYtAFzswhZKik9_W58GI4xhfmCLx2WM-7sBorggyWe7y9Fw3m7hN1IXZ462u2TKH-e00_lZ5HkPHF6oV_HZyp3eFhvKIuvuPOpZrqIKyH7gq0CCTDvReeSS75',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAfaLcj0AaS-RYmD02RX7Rgw4cdvYN-Te4txhMEnon7o165KFlx7305UPfag0QiOQ7-uzE42LuIeBjgnzpv_EjH8oeYEjD_ccTbfnf4Awz2k71EqZmWg8FpQnkqM3lOYtAFzswhZKik9_W58GI4xhfmCLx2WM-7sBorggyWe7y9Fw3m7hN1IXZ462u2TKH-e00_lZ5HkPHF6oV_HZyp3eFhvKIuvuPOpZrqIKyH7gq0CCTDvReeSS75',
    status: 'READY',
    scores: { walk: 9.4, presence: 9.2, garment: 9.5, originality: 9.3, total: 9.36 }
  },
  {
    id: 'M05',
    name: 'Sasha Grey',
    look: 'Tethered Leather Bonds',
    designer: 'Bell Design',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHCs14qm-xSaKNQuSnXzqdOxpqvNIQEfmLeMhUia4qG6tUwH-ABcVTWtOg4WdP26n7xuezTMA7_CBWRzbAKqsPi-c46ERpyYPiUthMlxJLvlt7UKZi9GmP7QnN4D7YMxuvwmNBowDkuiBG7uo-24Sw06tzFBUl6eRD-_z1aN2bcQoR2VJZSZGVopneasCGBcfauDUxnl8YxbQRwJhlc8CQuZI5xavLmAxQJK42Zsy3PN-NY6fFyqkU',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDtyc19x8Ee_GXqfoX2Glbys6CTAvH_vIbF4ZrTf1PWBSVkbfXGRTkVBmRSOgRSUrpcTLeggOLY4mqPsvmY9IRRams4FnwdE7Am6eL82kMYswX_htV3-PoyDVjICZgF51dDF054UZOKBEfOxaEJK5KGs8r8QrPjbuR9vdMzXvISKRJlUe8HIeSqcb5ZIinrqgrO629wv_EDnP3wbsc8UcTdjwX5k9sCxn4MdJLywjtzMLmoIWRLs3Ab',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDtyc19x8Ee_GXqfoX2Glbys6CTAvH_vIbF4ZrTf1PWBSVkbfXGRTkVBmRSOgRSUrpcTLeggOLY4mqPsvmY9IRRams4FnwdE7Am6eL82kMYswX_htV3-PoyDVjICZgF51dDF054UZOKBEfOxaEJK5KGs8r8QrPjbuR9vdMzXvISKRJlUe8HIeSqcb5ZIinrqgrO629wv_EDnP3wbsc8UcTdjwX5k9sCxn4MdJLywjtzMLmoIWRLs3Ab',
    status: 'READY',
    scores: { walk: 9.1, presence: 9.4, garment: 9.2, originality: 9.2, total: 9.23 }
  },
  {
    id: 'M06',
    name: 'Julian Marx',
    look: 'Architectural Wool Tunic',
    designer: 'Maison Ariste',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7cY6pWl9s4_N6dANJVc2AQGUS2TpdwM_8e-TcwFntxmMn40fBBJlJQVtuiZM3kf2Zp2GN9cjni_MMdBeXclbDYpm4AXPqJ9F0z6G3RANpmKuAt3d-vfAOBFUZ0gdu76T2H1HB17pO8kyDnIsgpVsgc2tonbjmK1f2qxsaReqw2rB0oSBRjQwJU2jQSwzq99vScdTjYCqwph894k7RlZqXlshHrp3XMmd7wvGCccXjdH0FchAJrsmo',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7cY6pWl9s4_N6dANJVc2AQGUS2TpdwM_8e-TcwFntxmMn40fBBJlJQVtuiZM3kf2Zp2GN9cjni_MMdBeXclbDYpm4AXPqJ9F0z6G3RANpmKuAt3d-vfAOBFUZ0gdu76T2H1HB17pO8kyDnIsgpVsgc2tonbjmK1f2qxsaReqw2rB0oSBRjQwJU2jQSwzq99vScdTjYCqwph894k7RlZqXlshHrp3XMmd7wvGCccXjdH0FchAJrsmo',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7cY6pWl9s4_N6dANJVc2AQGUS2TpdwM_8e-TcwFntxmMn40fBBJlJQVtuiZM3kf2Zp2GN9cjni_MMdBeXclbDYpm4AXPqJ9F0z6G3RANpmKuAt3d-vfAOBFUZ0gdu76T2H1HB17pO8kyDnIsgpVsgc2tonbjmK1f2qxsaReqw2rB0oSBRjQwJU2jQSwzq99vScdTjYCqwph894k7RlZqXlshHrp3XMmd7wvGCccXjdH0FchAJrsmo',
    status: 'READY',
    scores: { walk: 0, presence: 0, garment: 0, originality: 0, total: 0 }
  },
  {
    id: 'M07',
    name: 'Sophia Chen',
    look: 'Frosted Organza Layer',
    designer: 'Kerr & Co',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB0PJUGo07h5tw-d2N3Cfnb-Ca8CekvpRuUoM2Kt_bjYx-63RLBF--aLcFoNtk9IDHlwnEfwsVCIyYP9JZPn8qC2jI0ajmVR00T-hnTW3qByYvt_AHzVxWcTTPH4Yq4YQdth2FxBFN-EiJCLLtFUDjeKJc5JTzC7nUZFPoZfIZ3-s-pX2hmY5IgLyPpaE9sJX5WGDtAP_blAdUgMCo_TAZ3k9wF7wsDpdfu27pOhsPonMRm2YI-hB3d',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB0PJUGo07h5tw-d2N3Cfnb-Ca8CekvpRuUoM2Kt_bjYx-63RLBF--aLcFoNtk9IDHlwnEfwsVCIyYP9JZPn8qC2jI0ajmVR00T-hnTW3qByYvt_AHzVxWcTTPH4Yq4YQdth2FxBFN-EiJCLLtFUDjeKJc5JTzC7nUZFPoZfIZ3-s-pX2hmY5IgLyPpaE9sJX5WGDtAP_blAdUgMCo_TAZ3k9wF7wsDpdfu27pOhsPonMRm2YI-hB3d',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB0PJUGo07h5tw-d2N3Cfnb-Ca8CekvpRuUoM2Kt_bjYx-63RLBF--aLcFoNtk9IDHlwnEfwsVCIyYP9JZPn8qC2jI0ajmVR00T-hnTW3qByYvt_AHzVxWcTTPH4Yq4YQdth2FxBFN-EiJCLLtFUDjeKJc5JTzC7nUZFPoZfIZ3-s-pX2hmY5IgLyPpaE9sJX5WGDtAP_blAdUgMCo_TAZ3k9wF7wsDpdfu27pOhsPonMRm2YI-hB3d',
    status: 'JUDGED',
    scores: { walk: 8.8, presence: 8.9, garment: 9.1, originality: 9.0, total: 8.95 }
  },
  {
    id: 'M08',
    name: 'Elena Valenci',
    look: 'Look #42: Sculpted Silk & Tulle',
    designer: 'Maison Ariste',
    imageUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHwhsqNbdhjJKyjvOUexzf5tSSrjAFVKzoO39nAjvBCWF13DyexzZGCDaVWvKJEQZqJ6ZXQtlxNHfapI3APkVwnhWV3xsl3WvlUW8Bxd22fsM2F_yCpQZVF8RtDW-Sga7P9lcx6TdoNXTVbycBswwvwmsicrOwbahFoPww6unbKBQMNAaT4y9DKg4a9lcLsziifxoTUYmgdTHgbZBspFozxfBOBfxNSHBdwlKkegR5v-mXb06l4JBq',
    avatarUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHwhsqNbdhjJKyjvOUexzf5tSSrjAFVKzoO39nAjvBCWF13DyexzZGCDaVWvKJEQZqJ6ZXQtlxNHfapI3APkVwnhWV3xsl3WvlUW8Bxd22fsM2F_yCpQZVF8RtDW-Sga7P9lcx6TdoNXTVbycBswwvwmsicrOwbahFoPww6unbKBQMNAaT4y9DKg4a9lcLsziifxoTUYmgdTHgbZBspFozxfBOBfxNSHBdwlKkegR5v-mXb06l4JBq',
    podiumUrl: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAHwhsqNbdhjJKyjvOUexzf5tSSrjAFVKzoO39nAjvBCWF13DyexzZGCDaVWvKJEQZqJ6ZXQtlxNHfapI3APkVwnhWV3xsl3WvlUW8Bxd22fsM2F_yCpQZVF8RtDW-Sga7P9lcx6TdoNXTVbycBswwvwmsicrOwbahFoPww6unbKBQMNAaT4y9DKg4a9lcLsziifxoTUYmgdTHgbZBspFozxfBOBfxNSHBdwlKkegR5v-mXb06l4JBq',
    status: 'WALKING',
    scores: { walk: 0, presence: 0, garment: 0, originality: 0, total: 0 }
  }
];

export const useModelStore = defineStore('model', {
  state: () => ({
    models: JSON.parse(localStorage.getItem('aura_models')) || INITIAL_MODELS,
    selectedModelId: null
  }),
  getters: {
    selectedModel: (state) => state.models.find(m => m.id === state.selectedModelId) || null,
    rankedModels: (state) => [...state.models].sort((a, b) => b.scores.total - a.scores.total)
  },
  actions: {
    setSelectedModel(id) {
      this.selectedModelId = id;
    },
    updateScores(modelId, scores) {
      const model = this.models.find(m => m.id === modelId);
      if (model) {
        model.status = 'JUDGED';
        model.scores = scores;
        this.saveModels();
      }
    },
    saveModels() {
      localStorage.setItem('aura_models', JSON.stringify(this.models));
    }
  }
});
