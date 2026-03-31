export default function TechDashboard() {
  return (
    <div className="animate-in fade-in duration-500 space-y-8">
      <div className="flex justify-between items-end">
        <div>
          <h2 className="font-headline text-4xl font-extrabold text-on-surface tracking-tight mb-2">Dashboard Técnico</h2>
          <p className="text-on-surface-variant text-lg">Centralized field operations and site visit management.</p>
        </div>
        <div className="flex gap-3">
          <button className="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-full hover:bg-surface-container-highest transition-all flex items-center gap-2">
            <span className="material-symbols-outlined text-sm">calendar_month</span>
            Today's Schedule
          </button>
          <button className="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-container text-on-primary font-semibold rounded-full shadow-lg hover:shadow-primary/20 transition-all flex items-center gap-2">
            <span className="material-symbols-outlined text-sm">add</span>
            New Inspection
          </button>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-surface-container-lowest p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between">
          <div className="flex justify-between items-start mb-4">
            <div className="p-2 bg-primary-fixed text-on-primary-fixed rounded-lg">
              <span className="material-symbols-outlined">pending_actions</span>
            </div>
            <span className="text-xs font-bold text-primary font-label uppercase tracking-widest">Live</span>
          </div>
          <p className="text-on-surface-variant text-sm font-medium">Pending Visits</p>
          <h3 className="text-3xl font-extrabold font-headline mt-1">24</h3>
          <div className="mt-4 flex items-center gap-2 text-xs font-bold text-tertiary">
            <span className="material-symbols-outlined text-sm">trending_up</span>
            <span>12% from last week</span>
          </div>
        </div>

        <div className="bg-surface-container-lowest p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between">
          <div className="flex justify-between items-start mb-4">
            <div className="p-2 bg-error-container text-on-error-container rounded-lg">
              <span className="material-symbols-outlined">priority_high</span>
            </div>
            <span className="text-xs font-bold text-error font-label uppercase tracking-widest">Critical</span>
          </div>
          <p className="text-on-surface-variant text-sm font-medium">High Priority</p>
          <h3 className="text-3xl font-extrabold font-headline mt-1">07</h3>
          <div className="mt-4 flex items-center gap-2 text-xs font-bold text-error">
            <span className="material-symbols-outlined text-sm">warning</span>
            <span>Requires attention</span>
          </div>
        </div>

        <div className="bg-surface-container-lowest p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between">
          <div className="flex justify-between items-start mb-4">
            <div className="p-2 bg-secondary-container text-on-secondary-container rounded-lg">
              <span className="material-symbols-outlined">today</span>
            </div>
            <span className="text-xs font-bold text-secondary font-label uppercase tracking-widest">Active</span>
          </div>
          <p className="text-on-surface-variant text-sm font-medium">Inspections Today</p>
          <h3 className="text-3xl font-extrabold font-headline mt-1">05</h3>
          <div className="mt-4 flex items-center gap-2 text-xs font-bold text-on-surface-variant">
            <span className="material-symbols-outlined text-sm">schedule</span>
            <span>3/5 Completed</span>
          </div>
        </div>

        <div className="bg-surface-container-lowest p-6 rounded-xl border border-white/20 shadow-sm flex flex-col justify-between">
          <div className="flex justify-between items-start mb-4">
            <div className="p-2 bg-tertiary-fixed text-on-tertiary-fixed rounded-lg">
              <span className="material-symbols-outlined">person_add</span>
            </div>
            <span className="text-xs font-bold text-tertiary font-label uppercase tracking-widest">Queued</span>
          </div>
          <p className="text-on-surface-variant text-sm font-medium">Unassigned</p>
          <h3 className="text-3xl font-extrabold font-headline mt-1">12</h3>
          <div className="mt-4 flex items-center gap-2 text-xs font-bold text-tertiary">
            <span className="material-symbols-outlined text-sm">group</span>
            <span>Available for pickup</span>
          </div>
        </div>
      </div>

      {/* Main Layout: Bento Style */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Map View (8 cols) */}
        <div className="lg:col-span-8 bg-surface-container-low rounded-2xl overflow-hidden relative min-h-[500px] border border-white/40 shadow-xl group">
          <div className="absolute inset-0 z-0 bg-slate-200">
            <img alt="Regional map view" className="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD3y0Fr4ckYXoBaDM4cdhG-j7_QDxo6y8rR8DuINJRVftVF8wwQSiyFOqSF1U7ijvh3Gevoce-hu3KL54VAJsVYruSdQhE8N7F5ZGCH44RyF0j-Q5Als2OurmnKMewqh8kxcUMF6en9M-Pd1WVuK1IxZ2rEJl0y9yoDr4I3wMgfu24D-b8ZX4pA5vRQE7K88Am773tNjRfek-_t90TYn8n0ytkgj-Wr5QD29B41CmFgnB9F-ybQXO5Eb407DeK6IrTwdFPD85Vmx-hg" />
          </div>
          
          {/* Map Overlays */}
          <div className="absolute top-6 left-6 z-10 p-4 bg-white/80 backdrop-blur-md rounded-xl shadow-lg border border-white/20 max-w-xs">
            <h4 className="font-headline font-bold text-on-surface mb-1">Regional Clusters</h4>
            <p className="text-xs text-on-surface-variant mb-4">Visualizing density of pending inspection requests across the sector.</p>
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-xs flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-error"></span> North Zone</span>
                <span className="text-xs font-bold">12 Cases</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-xs flex items-center gap-2"><span className="w-2 h-2 rounded-full bg-secondary"></span> Central Hub</span>
                <span className="text-xs font-bold">08 Cases</span>
              </div>
            </div>
          </div>
          
          <div className="absolute bottom-6 right-6 z-10 flex gap-2">
            <button className="bg-white p-3 rounded-full shadow-lg hover:bg-slate-50 transition-colors">
              <span className="material-symbols-outlined">my_location</span>
            </button>
            <button className="bg-white p-3 rounded-full shadow-lg hover:bg-slate-50 transition-colors">
              <span className="material-symbols-outlined">layers</span>
            </button>
          </div>
        </div>

        {/* Recent Activities or Quick View (4 cols) */}
        <div className="lg:col-span-4 flex flex-col gap-6">
          <div className="bg-surface-container-lowest rounded-2xl p-6 border border-white/20 shadow-sm flex-1">
            <h4 className="font-headline font-bold text-xl mb-6 flex items-center gap-2">
              <span className="material-symbols-outlined text-secondary">bolt</span>
              Quick Actions
            </h4>
            <div className="space-y-4">
              <button className="w-full flex items-center justify-between p-4 bg-surface-container-low rounded-xl hover:bg-surface-container-high transition-all group">
                <div className="flex items-center gap-3">
                  <span className="material-symbols-outlined text-primary">route</span>
                  <div className="text-left">
                    <p className="text-sm font-bold">Optimize Route</p>
                    <p className="text-xs text-on-surface-variant">Best path for today's visits</p>
                  </div>
                </div>
                <span className="material-symbols-outlined text-outline group-hover:translate-x-1 transition-transform">chevron_right</span>
              </button>
              
              <button className="w-full flex items-center justify-between p-4 bg-surface-container-low rounded-xl hover:bg-surface-container-high transition-all group">
                <div className="flex items-center gap-3">
                  <span className="material-symbols-outlined text-tertiary">assignment_turned_in</span>
                  <div className="text-left">
                    <p className="text-sm font-bold">Bulk Resolve</p>
                    <p className="text-xs text-on-surface-variant">Update multiple low-priority</p>
                  </div>
                </div>
                <span className="material-symbols-outlined text-outline group-hover:translate-x-1 transition-transform">chevron_right</span>
              </button>
              
              <button className="w-full flex items-center justify-between p-4 bg-surface-container-low rounded-xl hover:bg-surface-container-high transition-all group">
                <div className="flex items-center gap-3">
                  <span className="material-symbols-outlined text-secondary">emergency_share</span>
                  <div className="text-left">
                    <p className="text-sm font-bold">Request Backup</p>
                    <p className="text-xs text-on-surface-variant">Notify regional supervisor</p>
                  </div>
                </div>
                <span className="material-symbols-outlined text-outline group-hover:translate-x-1 transition-transform">chevron_right</span>
              </button>
            </div>
          </div>

          <div className="bg-surface-container-lowest rounded-2xl p-6 border border-white/20 shadow-sm overflow-hidden relative">
            <div className="relative z-10">
              <h4 className="font-headline font-bold text-on-surface mb-4">Inspection Efficiency</h4>
              <div className="flex items-center gap-4 mb-6">
                <div className="relative w-20 h-20">
                  <svg className="w-full h-full transform -rotate-90">
                    <circle className="text-surface-container-high" cx="40" cy="40" fill="transparent" r="34" stroke="currentColor" strokeWidth="8"></circle>
                    <circle className="text-secondary" cx="40" cy="40" fill="transparent" r="34" stroke="currentColor" strokeDasharray="213.6" strokeDashoffset="42.7" strokeWidth="8"></circle>
                  </svg>
                  <span className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 font-headline font-bold text-sm">80%</span>
                </div>
                <div>
                  <p className="text-sm font-bold">Weekly Goal</p>
                  <p className="text-xs text-on-surface-variant">16/20 site visits completed</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Case Table Section */}
      <div>
        <div className="bg-surface-container-lowest rounded-2xl border border-white/20 shadow-sm overflow-hidden">
          <div className="px-8 py-6 border-b border-surface-container-low flex justify-between items-center">
            <h4 className="font-headline font-bold text-xl">Pending Inspection Queue</h4>
            <div className="flex gap-2">
              <select className="text-sm bg-surface-container-low border-none rounded-lg focus:ring-0">
                <option>All Species</option>
                <option>Canine</option>
                <option>Feline</option>
                <option>Equine</option>
              </select>
              <button className="p-2 bg-surface-container-low rounded-lg hover:bg-surface-container-high transition-colors">
                <span className="material-symbols-outlined text-lg">filter_list</span>
              </button>
            </div>
          </div>
          
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead>
                <tr className="bg-surface-container-low/50">
                  <th className="px-8 py-4 font-manrope text-xs font-bold uppercase tracking-widest text-on-surface-variant">Location (Map Link)</th>
                  <th className="px-8 py-4 font-manrope text-xs font-bold uppercase tracking-widest text-on-surface-variant">Animal Species</th>
                  <th className="px-8 py-4 font-manrope text-xs font-bold uppercase tracking-widest text-on-surface-variant text-center">Days Waiting</th>
                  <th className="px-8 py-4 font-manrope text-xs font-bold uppercase tracking-widest text-on-surface-variant">Status</th>
                  <th className="px-8 py-4 font-manrope text-xs font-bold uppercase tracking-widest text-on-surface-variant text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-surface-container-low">
                <tr className="hover:bg-surface-container-low/30 transition-colors group">
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded bg-primary-fixed/30 flex items-center justify-center text-primary">
                        <span className="material-symbols-outlined text-sm">location_on</span>
                      </div>
                      <div>
                        <p className="text-sm font-bold">Oakwood Heights, Unit 4B</p>
                        <a className="text-xs text-primary hover:underline flex items-center gap-1" href="#">View on Map <span className="material-symbols-outlined text-[10px]">open_in_new</span></a>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-outline">pets</span>
                      <span className="text-sm">Canine (Mixed Breed)</span>
                    </div>
                  </td>
                  <td className="px-8 py-5 text-center">
                    <span className="inline-flex px-2 py-1 bg-error-container text-on-error-container text-[10px] font-bold rounded-full">4 Days</span>
                  </td>
                  <td className="px-8 py-5">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-fixed text-on-primary-fixed text-xs font-semibold rounded-full">
                      <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                      High Priority
                    </span>
                  </td>
                  <td className="px-8 py-5 text-right">
                    <button className="bg-primary text-white text-xs font-bold px-4 py-2 rounded-full hover:bg-primary-container transition-all">Inspect</button>
                  </td>
                </tr>
                
                <tr className="hover:bg-surface-container-low/30 transition-colors group">
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded bg-primary-fixed/30 flex items-center justify-center text-primary">
                        <span className="material-symbols-outlined text-sm">location_on</span>
                      </div>
                      <div>
                        <p className="text-sm font-bold">Riverside Industrial Park</p>
                        <a className="text-xs text-primary hover:underline flex items-center gap-1" href="#">View on Map <span className="material-symbols-outlined text-[10px]">open_in_new</span></a>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-outline">agriculture</span>
                      <span className="text-sm">Equine (Stallion)</span>
                    </div>
                  </td>
                  <td className="px-8 py-5 text-center">
                    <span className="inline-flex px-2 py-1 bg-surface-container-high text-on-surface-variant text-[10px] font-bold rounded-full">1 Day</span>
                  </td>
                  <td className="px-8 py-5">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-tertiary-fixed text-on-tertiary-fixed text-xs font-semibold rounded-full">
                      <span className="w-1.5 h-1.5 rounded-full bg-tertiary"></span>
                      Standard
                    </span>
                  </td>
                  <td className="px-8 py-5 text-right">
                    <button className="bg-surface-container-high text-on-surface text-xs font-bold px-4 py-2 rounded-full hover:bg-surface-container-highest transition-all">Schedule</button>
                  </td>
                </tr>
                
                <tr className="hover:bg-surface-container-low/30 transition-colors group">
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded bg-primary-fixed/30 flex items-center justify-center text-primary">
                        <span className="material-symbols-outlined text-sm">location_on</span>
                      </div>
                      <div>
                        <p className="text-sm font-bold">Green Valley Estates</p>
                        <a className="text-xs text-primary hover:underline flex items-center gap-1" href="#">View on Map <span className="material-symbols-outlined text-[10px]">open_in_new</span></a>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-5">
                    <div className="flex items-center gap-2">
                      <span className="material-symbols-outlined text-outline">pets</span>
                      <span className="text-sm">Feline (Domestic)</span>
                    </div>
                  </td>
                  <td className="px-8 py-5 text-center">
                    <span className="inline-flex px-2 py-1 bg-error-container text-on-error-container text-[10px] font-bold rounded-full">3 Days</span>
                  </td>
                  <td className="px-8 py-5">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-primary-fixed text-on-primary-fixed text-xs font-semibold rounded-full">
                      <span className="w-1.5 h-1.5 rounded-full bg-primary"></span>
                      Moderate
                    </span>
                  </td>
                  <td className="px-8 py-5 text-right">
                    <button className="bg-primary text-white text-xs font-bold px-4 py-2 rounded-full hover:bg-primary-container transition-all">Inspect</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div className="px-8 py-4 bg-surface-container-low/50 flex items-center justify-between">
            <p className="text-xs text-on-surface-variant">Showing 3 of 12 unassigned cases</p>
            <div className="flex gap-2">
              <button className="p-1 hover:bg-white rounded transition-colors"><span className="material-symbols-outlined">chevron_left</span></button>
              <button className="p-1 hover:bg-white rounded transition-colors"><span className="material-symbols-outlined">chevron_right</span></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
