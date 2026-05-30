<div>

    <!-- ══ KPI CARDS ══ -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-pink"><span class="material-icons-round">shopping_bag</span></div>
          <div class="stat-label">আজকের অর্ডার</div>
          <div class="stat-value mono">47</div>
          <div class="stat-change stat-up"><span class="material-icons-round">arrow_upward</span>+18% গতকাল থেকে</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-green"><span class="material-icons-round">payments</span></div>
          <div class="stat-label">আজকের বিক্রয়</div>
          <div class="stat-value mono">৳8,240</div>
          <div class="stat-change stat-up"><span class="material-icons-round">arrow_upward</span>+24%</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-orange"><span class="material-icons-round">schedule</span></div>
          <div class="stat-label">গড় প্রস্তুতি সময়</div>
          <div class="stat-value mono">22 মি.</div>
          <div class="stat-change stat-up"><span class="material-icons-round">arrow_upward</span>দ্রুততম আজ</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon-wrap ic-blue"><span class="material-icons-round">star_rate</span></div>
          <div class="stat-label">রেটিং</div>
          <div class="stat-value mono">4.8★</div>
          <div class="stat-change stat-up"><span class="material-icons-round">emoji_events</span>টপ রেটেড!</div>
        </div>
      </div>
    </div>

    <!-- ══ FEATURED FOOD BANNER ══ -->
    <div class="hero-banner mb-3">
      <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1400&q=80" alt="Restaurant Banner"/>
      <div class="hero-overlay">
        <div class="hero-content">
          <h1>মা'র রান্নাঘর<br/><span>সেরা বাংলা খাবার</span></h1>
          <p>আজ ৪৭টি অর্ডার সম্পন্ন — আপনি দারুণ করছেন! 🎉</p>
          <button class="btn-pink"><span class="material-icons-round">add_circle</span> নতুন আইটেম যোগ করুন</button>
        </div>
      </div>
    </div>

    <!-- ══ CHART + LIVE ORDERS ══ -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-lg-7">
        <div class="dash-card h-100">
          <div class="dash-card-header grad-dark">
            <h6>সাপ্তাহিক বিক্রয়</h6>
            <p>এই সপ্তাহের দিন-ওয়ারি বিক্রয় তুলনা</p>
          </div>
          <div class="dash-card-body"><canvas id="vendorChart" height="160"></canvas></div>
        </div>
      </div>
      <div class="col-12 col-lg-5">
        <div class="dash-card h-100">
          <div class="dash-card-header grad-pink">
            <h6>🔴 লাইভ অর্ডার</h6>
            <p><span class="live-dot"></span>7টি অপেক্ষায় আছে</p>
          </div>
          <div class="dash-card-body p-0">
            <div id="liveOrdersList"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ TOP MENU ITEMS (full-width images) ══ -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <span style="font-size:.95rem;font-weight:800;">🍽️ সর্বাধিক বিক্রীত আইটেম</span>
      <a href="vendor-menu.html" class="btn-ghost-sm"><span class="material-icons-round">edit</span> মেনু এডিট</a>
    </div>
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="position:relative;">
          <span class="food-card-badge">28 বিক্রি</span>
          <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=75" class="food-card-img" alt="Food"/>
          <div class="food-card-body">
            <div class="food-card-name">ভাত + মুরগির তরকারি</div>
            <div class="food-card-price">৳120</div>
            <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:80%"></div></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="position:relative;">
          <span class="food-card-badge">22 বিক্রি</span>
          <img src="https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&q=75" class="food-card-img" alt="Food"/>
          <div class="food-card-body">
            <div class="food-card-name">মুরগির বিরিয়ানি</div>
            <div class="food-card-price">৳180</div>
            <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:65%"></div></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="position:relative;">
          <img src="https://images.unsplash.com/photo-1615361200141-f45040f367be?w=400&q=75" class="food-card-img" alt="Food"/>
          <div class="food-card-body">
            <div class="food-card-name">রুই মাছের ঝোল</div>
            <div class="food-card-price">৳140</div>
            <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:52%"></div></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="position:relative;">
          <img src="https://images.unsplash.com/photo-1567337710282-00832b415979?w=400&q=75" class="food-card-img" alt="Food"/>
          <div class="food-card-body">
            <div class="food-card-name">সবজি খিচুড়ি</div>
            <div class="food-card-price">৳100</div>
            <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:38%"></div></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="position:relative;">
          <img src="https://images.unsplash.com/photo-1601050690597-df0568f70950?w=400&q=75" class="food-card-img" alt="Food"/>
          <div class="food-card-body">
            <div class="food-card-name">পরোটা + ডিম ভাজি</div>
            <div class="food-card-price">৳80</div>
            <div class="kk-progress mt-1"><div class="kk-progress-bar" style="width:30%"></div></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="food-card" style="cursor:pointer;border:2px dashed var(--pink-mid);background:var(--pink-ultra-soft, #fff5fb);display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:180px;">
          <span class="material-icons-round" style="font-size:32px;color:var(--pink);margin-bottom:8px;">add_circle_outline</span>
          <div style="font-size:.78rem;font-weight:700;color:var(--pink)">নতুন আইটেম</div>
          <div style="font-size:.7rem;color:var(--muted);text-align:center;padding:0 10px;margin-top:4px">যোগ করতে ক্লিক করুন</div>
        </div>
      </div>
    </div>

    <!-- ══ MENU MANAGE TABLE (tenant-style) ══ -->
    <div class="dash-card mb-3">
      <div class="dash-card-header grad-dark">
        <h6>মেনু তালিকা ও স্ট্যাটাস ম্যানেজমেন্ট</h6>
        <p>সব আইটেমের মূল্য, ক্যাটাগরি ও প্রাপ্যতা</p>
      </div>
      <div class="dash-card-body">
        <div class="table-search-bar">
          <div class="table-search">
            <span class="material-icons-round">search</span>
            <input type="text" placeholder="আইটেম খুঁজুন..." id="menuSearch"/>
          </div>
          <select class="form-select d-none d-md-block" style="width:auto;font-size:.8rem;border-radius:10px;border-color:var(--border);padding:6px 12px;">
            <option>সব ক্যাটাগরি</option>
            <option>ভাত</option>
            <option>বিরিয়ানি</option>
            <option>মাছ</option>
          </select>
          <button class="btn-pink btn-pink-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addMenuModal">
            <span class="material-icons-round">add</span> আইটেম যোগ
          </button>
        </div>
        <div class="table-responsive">
          <table class="mat-table">
            <thead>
              <tr>
                <th onclick="sortTable(0)">আইটেম <span class="material-icons-round" style="font-size:12px!important;vertical-align:middle;">unfold_more</span></th>
                <th>ক্যাটাগরি</th>
                <th onclick="sortTable(2)">মূল্য <span class="material-icons-round" style="font-size:12px!important;vertical-align:middle;">unfold_more</span></th>
                <th>আজকের বিক্রি</th>
                <th>স্ট্যাটাস</th>
                <th>অ্যাকশন</th>
              </tr>
            </thead>
            <tbody id="menuTableBody"></tbody>
          </table>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
          <span style="font-size:.75rem;color:var(--muted)" id="menuPageInfo">Showing 1–5 of 12</span>
          <div id="menuPagination" class="d-flex gap-1"></div>
        </div>
      </div>
    </div>

  </div>

  @push('scripts')
      <script>
          function toggleSidebar() {
            document.getElementById('mainSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
          }
          function closeSidebar() {
            document.getElementById('mainSidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
          }
          function toggleNav1(el) {
            el.classList.toggle('open');
            el.nextElementSibling.classList.toggle('open');
          }

          /* ─── Chart ─── */
          new Chart(document.getElementById('vendorChart'), {
            type: 'bar',
            data: {
              labels: ['শুক্র','শনি','রবি','সোম','মঙ্গল','বুধ','বৃহ'],
              datasets: [{
                label: 'বিক্রয় (৳)',
                data: [4250,6000,4750,7000,5500,8000,8240],
                backgroundColor: ['rgba(233,30,140,.35)','rgba(233,30,140,.35)','rgba(233,30,140,.35)','rgba(233,30,140,.35)','rgba(233,30,140,.35)','rgba(233,30,140,.35)','#e91e8c'],
                borderRadius: 8, borderSkipped: false
              }]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ৳'+ctx.raw.toLocaleString() } } },
              scales: { x: { grid: { display: false } }, y: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { callback: v => '৳'+(v/1000)+'k' } } }
            }
          });

          /* ─── Live Orders ─── */
          const liveOrders = [
            { id:'#KK2615', cust:'নাসরিন', item:'ভাত + মুরগি', total:'৳250', status:'নতুন', sc:'status-pend', time:'এইমাত্র' },
            { id:'#KK2614', cust:'রফিক', item:'বিরিয়ানি ×2', total:'৳360', status:'রান্না হচ্ছে', sc:'status-in', time:'8 মিনিট' },
            { id:'#KK2613', cust:'শিমু', item:'চিকেন ফ্রাই', total:'৳180', status:'প্রস্তুত', sc:'status-in', time:'18 মিনিট' },
          ];
          document.getElementById('liveOrdersList').innerHTML = liveOrders.map(o => `
            <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;">
              <div style="flex:1;">
                <div style="font-size:.75rem;font-weight:700;color:var(--pink);">${o.id}</div>
                <div style="font-size:.8rem;font-weight:600;">${o.cust} — ${o.item}</div>
                <div style="font-size:.7rem;color:var(--muted);">${o.time} আগে • ${o.total}</div>
              </div>
              <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                <span class="status-badge ${o.sc}" style="padding:2px 8px;font-size:.65rem;"><span class="status-dot"></span>${o.status}</span>
                <button class="btn-pink btn-pink-sm">আপডেট</button>
              </div>
            </div>`).join('');

          /* ─── Menu Table ─── */
          const menuItems = [
            { img:'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=80&q=70', name:'ভাত + মুরগির তরকারি', cat:'ভাত', price:'৳120', sold:28, active:true },
            { img:'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=80&q=70', name:'মুরগির বিরিয়ানি', cat:'বিরিয়ানি', price:'৳180', sold:22, active:true },
            { img:'https://images.unsplash.com/photo-1615361200141-f45040f367be?w=80&q=70', name:'রুই মাছের ঝোল', cat:'মাছ', price:'৳140', sold:18, active:true },
            { img:'https://images.unsplash.com/photo-1567337710282-00832b415979?w=80&q=70', name:'সবজি খিচুড়ি', cat:'ভাত', price:'৳100', sold:14, active:true },
            { img:'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=80&q=70', name:'পরোটা + ডিম ভাজি', cat:'নাস্তা', price:'৳80', sold:11, active:false },
          ];

          function renderMenuTable() {
            document.getElementById('menuTableBody').innerHTML = menuItems.map((m, i) => `
              <tr>
                <td class="prod-td">
                  <div class="prod-cell">
                    <img src="${m.img}" class="prod-thumb" alt="${m.name}" onerror="this.src='https://placehold.co/44x44?text=IMG'"/>
                    <div><div class="prod-name">${m.name}</div></div>
                  </div>
                </td>
                <td><span class="cat-badge">${m.cat}</span></td>
                <td style="font-weight:700;color:var(--pink)">${m.price}</td>
                <td><span style="font-size:.78rem;font-weight:700;">${m.sold}টি</span><div class="kk-progress mt-1" style="width:60px;"><div class="kk-progress-bar" style="width:${m.sold/28*100}%"></div></div></td>
                <td>
                  <span class="status-badge ${m.active?'status-in':'status-out'}">
                    <span class="status-dot"></span>${m.active?'সক্রিয়':'বন্ধ'}
                  </span>
                </td>
                <td>
                  <div class="action-btns">
                    <button class="act-btn view" title="View"><span class="material-icons-round">visibility</span></button>
                    <button class="act-btn edit" title="Edit"><span class="material-icons-round">drive_file_rename_outline</span></button>
                    <button class="act-btn delete" title="Delete"><span class="material-icons-round">delete</span></button>
                  </div>
                </td>
              </tr>`).join('');
            document.getElementById('menuPagination').innerHTML =
              `<button style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid rgba(0,0,0,.1);background:linear-gradient(135deg,#e91e8c,#ff4dab);color:#fff;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit;">1</button>
              <button style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid rgba(0,0,0,.1);background:#fff;font-size:.78rem;font-weight:600;cursor:pointer;font-family:inherit;">2</button>`;
          }
          renderMenuTable();

          document.getElementById('menuSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            const filtered = menuItems.filter(m => m.name.toLowerCase().includes(q) || m.cat.toLowerCase().includes(q));
            document.getElementById('menuTableBody').innerHTML = filtered.map(m => `
              <tr>
                <td class="prod-td"><div class="prod-cell"><img src="${m.img}" class="prod-thumb" alt="${m.name}"/><div class="prod-name">${m.name}</div></div></td>
                <td><span class="cat-badge">${m.cat}</span></td>
                <td style="font-weight:700;color:var(--pink)">${m.price}</td>
                <td>${m.sold}টি</td>
                <td><span class="status-badge ${m.active?'status-in':'status-out'}"><span class="status-dot"></span>${m.active?'সক্রিয়':'বন্ধ'}</span></td>
                <td><div class="action-btns"><button class="act-btn view"><span class="material-icons-round">visibility</span></button><button class="act-btn edit"><span class="material-icons-round">drive_file_rename_outline</span></button><button class="act-btn delete"><span class="material-icons-round">delete</span></button></div></td>
              </tr>`).join('');
            document.getElementById('menuPageInfo').textContent = `Showing 1–${filtered.length} of ${filtered.length}`;
          });
      </script>
  @endpush