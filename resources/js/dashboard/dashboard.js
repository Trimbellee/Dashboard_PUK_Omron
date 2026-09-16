/* ═══════════════════════════════════════════════════════════
   BOOTSTRAP DATA DARI BLADE (lihat window.DASHBOARD_DATA
   yang di-set inline di dashboard.blade.php)
═══════════════════════════════════════════════════════════ */
const DD = window.DASHBOARD_DATA || {};
const KEU = DD.KEU || {};
const DASH_USERNAME = DD.DASH_USERNAME;
const DASH_ROLE = DD.DASH_ROLE;
const initialDashboardData = DD.initialDashboardData;
let belumLaporData = DD.belumLaporData || {};
const DASHBOARD_URL = DD.dashboardUrl;
const DETAIL_BIDANG_URL = DD.detailBidangUrl;
const UPLOAD_LAPORAN_URL = DD.uploadLaporanUrl;
const CSRF_TOKEN = DD.csrfToken;

/* ═══════════════════════════════════════════════════════════
   GRAFIK #1 + #2 + #3 (Tren Keuangan, Bidang, Rasio, Debit/Kredit)
═══════════════════════════════════════════════════════════ */
(function() {

const isDark    = window.matchMedia('(prefers-color-scheme: dark)').matches;
const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
const txtColor  = isDark ? '#94a3b8' : '#6b7280';
const isMobile  = window.innerWidth <= 640;

const BULAN_LABEL = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const BULAN_FULL  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function fmtRp(n) {
    if (n === null || n === undefined) return 'Rp 0';
    return 'Rp ' + Number(n).toLocaleString('id-ID');
}
function round1(n) { return Math.round(n * 10) / 10; }
function shortNominal(v) {
    if (!v) return '';
    function fmtDec(n, s) { let r=Math.round(n*100)/100; return (Number.isInteger(r)?r:r.toString().replace(/\.?0+$/,''))+s; }
    if (v >= 1000000000) return fmtDec(v/1000000000,'M');
    if (v >= 1000000)    return fmtDec(v/1000000,'jt');
    if (v >= 1000)       return Math.round(v/1000)+'rb';
    return v;
}

/* ══════════════════ GRAFIK #1 + #3 ══════════════════ */
let trenKeuChartInst = null;
let bidangChartInst  = null;
let rasioChartInst   = null;
let keuMode = 'month';
const keuCache = {};

const BIDANG_DISPLAY = {
    'SEKRETARIS':'Sekretaris','BIDANG 1':'Bidang 1','BIDANG 2':'Bidang 2',
    'BIDANG 3':'Bidang 3','BIDANG 4':'Bidang 4','BIDANG 5':'Bidang 5',
    'BIDANG 6':'Bidang 6','GM':'GM','KORLAP':'Korlap'
};
const BIDANG_COLOR = {
    'SEKRETARIS':'#378add','BIDANG 1':'#3d9a5e','BIDANG 2':'#e24b4a',
    'BIDANG 3':'#ba7517','BIDANG 4':'#7f77dd','BIDANG 5':'#d4537e',
    'BIDANG 6':'#1d9e75','GM':'#985b3b','KORLAP':'#639922'
};

function waitForChart(cb) {
    if (window.Chart) { cb(); return; }
    const t = setInterval(() => { if (window.Chart) { clearInterval(t); cb(); } }, 50);
}

waitForChart(() => {

    // ── Populate selects ──
    (function() {
        const years = KEU.tahunList?.length ? KEU.tahunList : [new Date().getFullYear()];
        const selMY = document.getElementById('keuMonthYear');
        years.forEach(y => selMY.appendChild(new Option(y,y,y===KEU.tahunAktif,y===KEU.tahunAktif)));
        ['keuMonthFrom','keuMonthTo'].forEach((id,idx) => {
            const sel=document.getElementById(id);
            BULAN_FULL.forEach((b,i)=>sel.appendChild(new Option(b,i+1,idx===0&&i===0,idx===1&&i===11)));
        });
        ['keuYearFrom','keuYearTo'].forEach((id,idx) => {
            const sel=document.getElementById(id); const minY=Math.min(...years);
            years.forEach(y=>sel.appendChild(new Option(y,y,idx===0&&y===minY,idx===1&&y===KEU.tahunAktif)));
        });
        const selMY2=document.getElementById('dkMonthYear');
        years.forEach(y=>selMY2.appendChild(new Option(y,y,y===KEU.tahunAktif,y===KEU.tahunAktif)));
        ['dkMonthFrom','dkMonthTo'].forEach((id,idx) => {
            const sel=document.getElementById(id);
            BULAN_FULL.forEach((b,i)=>sel.appendChild(new Option(b,i+1,idx===0&&i===0,idx===1&&i===11)));
        });
        ['dkYearFrom','dkYearTo'].forEach((id,idx) => {
            const sel=document.getElementById(id); const minY=Math.min(...years);
            years.forEach(y=>sel.appendChild(new Option(y,y,idx===0&&y===minY,idx===1&&y===KEU.tahunAktif)));
        });
    })();

    function updateKeuTitle(mode,ctx) {
        const el=document.getElementById('trenKeuTitle');
        if(mode==='year'){const fY=document.getElementById('keuYearFrom').value,tY=document.getElementById('keuYearTo').value;el.textContent=fY===tY?`Tren Dana — ${fY}`:`Tren Dana — ${fY}–${tY}`;}
        else{const fM=parseInt(document.getElementById('keuMonthFrom').value),tM=parseInt(document.getElementById('keuMonthTo').value);el.textContent=fM===tM?`Tren Dana — ${BULAN_FULL[fM-1]} ${ctx.tahun}`:`Tren Dana — ${BULAN_FULL[fM-1]}–${BULAN_FULL[tM-1]} ${ctx.tahun}`;}
    }

    window.setKeuMode=function(mode){
        keuMode=mode;
        ['Month','Year'].forEach(m=>{
            const active=m.toLowerCase()===mode;
            const btn=document.getElementById('btnKeuMode'+m);
            btn.style.background=active?'var(--accent-muted)':'transparent';
            btn.style.color=active?'var(--accent-text)':'var(--text-secondary)';
            btn.style.fontWeight=active?'600':'400';
            document.getElementById('keuRange'+m).style.display=active?'flex':'none';
        });
        mode==='month'?applyKeuMonthRange():applyKeuYearRange();
    };

    async function fetchKeuTahun(tahun){
        if(keuCache[tahun])return keuCache[tahun];
        const res=await fetch(`${KEU.trenKeuUrl}?tahun=${tahun}`,{headers:{'X-CSRF-TOKEN':KEU.csrfToken,'Accept':'application/json'}});
        keuCache[tahun]=await res.json(); return keuCache[tahun];
    }
    function setKeuLoading(on){
        document.getElementById('trenKeuLoading').style.display=on?'block':'none';
        document.getElementById('trenKeuChart').style.opacity=on?'0.2':'1';
    }

    window.applyKeuMonthRange = async function() {
        const tahun = parseInt(document.getElementById('keuMonthYear').value);
        let fromM   = parseInt(document.getElementById('keuMonthFrom').value);
        let toM     = parseInt(document.getElementById('keuMonthTo').value);
        if (fromM > toM) { document.getElementById('keuMonthTo').value = fromM; toM = fromM; }
        const now = new Date();
        if (tahun === now.getFullYear()) {
            toM = Math.min(toM, now.getMonth() + 1);
            document.getElementById('keuMonthTo').value = toM;
        }
        setKeuLoading(true);
        try {
            const data = await fetchKeuTahun(tahun);
            const fi = fromM - 1, ti = toM - 1;
            const sliced = {
                labels:    BULAN_LABEL.slice(fi, ti + 1),
                disetujui: data.disetujui.slice(fi, ti + 1),
                aktual:    data.aktual.slice(fi, ti + 1),
                tahun, mode: 'month'
            };
            updateKeuTitle('month', { tahun });
            buildTrenKeuChart(sliced);
            buildTrenKeuStats(sliced);
            const bs = {}, rs = {};
            const bl = data.bulanTerakhirLaporan ?? 0; 
            (data.bidangTetap || []).forEach(b => {
                bs[b] = (data.bidangPerBulan[b] || []).slice(fi, ti + 1);
                rs[b] = (data.rasioPerBulan[b] || []).slice(fi, ti + 1).map((val, idx) => {
                    const bulanAbsolut = fromM + idx;
                    return (bl > 0 && bulanAbsolut <= bl) ? val : null;
                });
            });

            buildBidangChart({
                labels:        BULAN_LABEL.slice(fi, ti + 1),
                bidangTetap:   data.bidangTetap || [],
                bidangPerBulan: bs,
                rasioPerBulan:  rs,
                bulanTerakhirLaporan: bl,
                tahun,
            });
        } catch(e) { console.error(e); }
        finally    { setKeuLoading(false); }
    };

    window.applyKeuYearRange = async function() {
        let fromY = parseInt(document.getElementById('keuYearFrom').value);
        let toY   = parseInt(document.getElementById('keuYearTo').value);
        if (fromY > toY) { document.getElementById('keuYearTo').value = fromY; toY = fromY; }
        setKeuLoading(true);
        try {
            const years   = [];
            for (let y = fromY; y <= toY; y++) years.push(y);
            const allData = await Promise.all(years.map(fetchKeuTahun));
            const labels    = years.map(String);
            const disetujui = allData.map(d => d.disetujui.reduce((a,b) => a+b, 0));
            const aktual    = allData.map(d => d.aktual.reduce((a,b) => a+b, 0));
            const sliced    = { labels, disetujui, aktual, tahun: `${fromY}–${toY}`, mode: 'year' };
            updateKeuTitle('year', {});
            buildTrenKeuChart(sliced);
            buildTrenKeuStats(sliced);
            const bt = allData[0]?.bidangTetap || [];
            const bpb = {}, rpb = {};
            bt.forEach(b => {
                bpb[b] = allData.map(d => (d.bidangPerBulan[b] || []).reduce((a,v) => a+v, 0));
                rpb[b] = allData.map(d => {
                    const bl = d.bulanTerakhirLaporan ?? 0;
                    if (bl === 0) return null;
                    const tb = (d.bidangPerBulan[b] || []).slice(0, bl).reduce((a,v) => a+v, 0);
                    const to = (d.danaOperPerBulan || []).slice(0, bl).reduce((a,v) => a+v, 0);
                    return to > 0 ? round1((tb / to) * 100) : null;
                });
            });
            
            buildBidangChart({
                labels,
                bidangTetap:    bt,
                bidangPerBulan: bpb,
                rasioPerBulan:  rpb,
                bulanTerakhirLaporan: allData[allData.length - 1]?.bulanTerakhirLaporan ?? 0,
                tahun: toY,
            });
        } catch(e) { console.error(e); }
        finally    { setKeuLoading(false); }
    };

    function buildTrenKeuChart(data){
        const ctx=document.getElementById('trenKeuChart');
        if(trenKeuChartInst)trenKeuChartInst.destroy();
        const rasio=data.disetujui.map((d,i)=>d>0?round1(((data.aktual[i]??0)/d)*100):0);
        trenKeuChartInst=new Chart(ctx,{
            type:'bar',
            data:{labels:data.labels,datasets:[
                {type:'bar',label:'Dana Disetujui',data:data.disetujui,backgroundColor:isDark?'rgba(55,138,221,0.82)':'rgba(55,138,221,0.78)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'bar',label:'Aktual Biaya',data:data.aktual,backgroundColor:isDark?'rgba(61,154,94,0.82)':'rgba(61,154,94,0.78)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'line',label:'Rasio (%)',data:rasio,borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,0.12)',borderWidth:2.5,pointRadius:isMobile?2:4,pointHoverRadius:6,pointBackgroundColor:'#f59e0b',pointBorderColor:isDark?'#181C26':'#fff',pointBorderWidth:2,tension:0.35,fill:false,yAxisID:'yRight',order:1,datalabels:{display:false}},
            ]},
            options:{
                responsive:true,maintainAspectRatio:false,layout:{padding:{top:isMobile?20:35}},
                interaction:{mode:'index',intersect:false},
                plugins:{
                    legend:{display:false},
                    datalabels:{display:!isMobile&&(ctx=>ctx.dataset.type==='bar'),anchor:'end',align:'end',rotation:-90,offset:2,color:isDark?'#e6e8ec':'#374151',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)},
                    tooltip:{callbacks:{label:ctx=>ctx.dataset.label==='Rasio (%)'?` Rasio: ${ctx.parsed.y}%`:` ${ctx.dataset.label}: ${fmtRp(ctx.parsed.y)}`}}
                },
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:isMobile,maxRotation:0,font:{size:isMobile?9:11}}},
                    yLeft:{type:'linear',position:'left',grid:{color:gridColor},beginAtZero:true,grace:'20%',ticks:{font:{size:isMobile?9:11},callback:v=>shortNominal(v)}},
                    yRight:{type:'linear',position:'right',grid:{drawOnChartArea:false},min:0,grace:'20%',ticks:{font:{size:isMobile?9:11},color:'#f59e0b',callback:v=>v+'%'}}
                }
            }
        });
    }

    function buildTrenKeuStats(data){
        if(isMobile){document.getElementById('trenKeuStats').innerHTML='';return;}
        const td=data.disetujui.reduce((a,b)=>a+(b??0),0);
        const ta=data.aktual.reduce((a,b)=>a+(b??0),0);
        const selisih=td-ta; const persen=td>0?round1(ta/td*100):0;
        document.getElementById('trenKeuStats').innerHTML=`
            <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Total Disetujui</div><div style="font-size:18px;font-weight:600;color:#378add;">${fmtRp(td)}</div></div>
            <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Total Aktual</div><div style="font-size:18px;font-weight:600;color:#3d9a5e;">${fmtRp(ta)}</div></div>
            <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Selisih</div><div style="font-size:18px;font-weight:600;color:${selisih>=0?'#3d9a5e':'#e24b4a'};">${fmtRp(selisih)}</div></div>
            <div><div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Persentase Realisasi</div><div style="font-size:18px;font-weight:600;color:#f59e0b;">${persen}%</div></div>`;
    }

    function buildBidangChart(payload){
        const{labels,bidangTetap,bidangPerBulan,rasioPerBulan}=payload;
        
        const noteEl = document.getElementById('rasioLaporanNote');
        if (noteEl) {
            if (payload.bulanTerakhirLaporan > 0) {
                noteEl.style.display = 'inline-flex';
                noteEl.textContent = `📋 Data laporan tersedia s/d ${BULAN_FULL[payload.bulanTerakhirLaporan - 1]} ${payload.tahun ?? ''}`;
            } else {
                noteEl.style.display = 'inline-flex';
                const tahunLabel = keuMode === 'month'
                    ? document.getElementById('keuMonthYear').value
                    : `${document.getElementById('keuYearFrom').value}–${document.getElementById('keuYearTo').value}`;
                noteEl.textContent = `📋 Belum ada laporan ${tahunLabel}`;
            }
        }
        document.getElementById('bidangLegend').innerHTML=(bidangTetap||[]).map(b=>`<span style="display:flex;align-items:center;gap:3px;"><span style="width:8px;height:8px;border-radius:2px;background:${BIDANG_COLOR[b]};flex-shrink:0;"></span>${BIDANG_DISPLAY[b]??b}</span>`).join('');
        if(!labels?.length||!bidangTetap?.length){
            document.getElementById('bidangWrap').innerHTML=`<div style="text-align:center;padding:24px 0;font-size:13px;color:var(--text-muted);">Tidak ada data</div>`;
            document.getElementById('rasioWrap').innerHTML=`<div style="text-align:center;padding:24px 0;font-size:13px;color:var(--text-muted);">Tidak ada data rasio</div>`;
            return;
        }
        if(!document.getElementById('bidangChart'))document.getElementById('bidangWrap').innerHTML='<canvas id="bidangChart"></canvas>';
        const canvasBar=document.getElementById('bidangChart');
        if(bidangChartInst)bidangChartInst.destroy();
        bidangChartInst=new Chart(canvasBar,{
            type:'bar',
            data:{labels,datasets:bidangTetap.map(b=>({label:BIDANG_DISPLAY[b]??b,data:bidangPerBulan[b]||[],backgroundColor:(BIDANG_COLOR[b]||'#378add')+(isDark?'bb':'cc'),borderRadius:3,borderSkipped:false}))},
            options:{
                responsive:true,maintainAspectRatio:false,layout:{padding:{top:isMobile?18:35}},
                interaction:{mode:'index',intersect:false},
                onClick(e){
                    const pts=bidangChartInst.getElementsAtEventForMode(e,'nearest',{intersect:true},true);
                    if(!pts.length)return;
                    const el=pts[0],bidang=payload.bidangTetap[el.datasetIndex],bi=el.index;
                    let tahun,bulan;
                    if(keuMode==='month'){tahun=document.getElementById('keuMonthYear').value;bulan=parseInt(document.getElementById('keuMonthFrom').value)+bi;}
                    else{tahun=parseInt(document.getElementById('keuYearFrom').value)+bi;bulan='semua';}
                    window.showBidangDetail(bidang,tahun,bulan);
                },
                plugins:{
                    legend:{display:false},
                    datalabels:{display:!isMobile,anchor:'end',align:'end',rotation:-90,offset:2,clamp:true,clip:false,color:isDark?'#e5e7eb':'#374151',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)},
                    tooltip:{mode:'index',intersect:false,itemSort:(a,b)=>a.datasetIndex-b.datasetIndex,
                    callbacks:{
                        label: ctx => ` ${ctx.dataset.label}: ${fmtRp(ctx.parsed.y)}`,
                        footer: items => {
                            const total = items.reduce((sum, i) => sum + (Number(i.parsed.y) || 0), 0);
                            return `Total: ${fmtRp(total)}`;
                        }
                    }}
                },
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:isMobile,maxRotation:0,font:{size:isMobile?9:11}}},
                    y:{type:'linear',position:'right',grid:{color:gridColor},beginAtZero:true,grace:'15%',ticks:{font:{size:isMobile?9:11},callback:v=>shortNominal(v)}}
                }
            }
        });
        if(!document.getElementById('rasioChart'))document.getElementById('rasioWrap').innerHTML='<canvas id="rasioChart"></canvas>';
        const canvasRasio=document.getElementById('rasioChart');
        if(rasioChartInst)rasioChartInst.destroy();
        rasioChartInst=new Chart(canvasRasio,{
            type:'bar',
            data:{labels,datasets:bidangTetap.map(b=>({label:BIDANG_DISPLAY[b]??b,data:rasioPerBulan[b]||[],backgroundColor:BIDANG_COLOR[b]||'#378add',borderRadius:4}))},
            options:{
                responsive:true,maintainAspectRatio:false,layout:{padding:{top:isMobile?18:35}},
                interaction:{mode:'index',intersect:false},
                onClick(e){
                    const pts=rasioChartInst.getElementsAtEventForMode(e,'nearest',{intersect:true},true);
                    if(!pts.length)return;
                    const el=pts[0],bidang=payload.bidangTetap[el.datasetIndex],bi=el.index;
                    let tahun,bulan;
                    if(keuMode==='month'){tahun=document.getElementById('keuMonthYear').value;bulan=parseInt(document.getElementById('keuMonthFrom').value)+bi;}
                    else{tahun=parseInt(document.getElementById('keuYearFrom').value)+bi;bulan='semua';}
                    window.showBidangDetail(bidang,tahun,bulan);
                },
                plugins:{
                    legend:{display:false},
                    datalabels: {display: !isMobile,anchor: 'end', align: 'end', rotation: -90, offset: 2,clamp: true, clip: false,color: isDark ? '#e5e7eb' : '#374151',font: { size: 9 },formatter: v => (v !== null && v > 0) ? v + '%' : '',},
                    tooltip:{mode:'index',intersect:false,callbacks: {label(ctx) {if (ctx.parsed.y === null) return null; return ` ${ctx.dataset.label}: ${ctx.parsed.y}%`;},
                    footer(items) {const validItems = items.filter(i => i.parsed.y !== null);if (!validItems.length) return '';const total = validItems.reduce((sum, i) => sum + (Number(i.parsed.y) || 0), 0);return `Total: ${Number.isInteger(total) ? total : total.toFixed(2)}%`;}}}
                },
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:isMobile,maxRotation:0,font:{size:isMobile?9:11}}},
                    y:{type:'linear',position:'right',grid:{color:gridColor},min:0,ticks:{font:{size:isMobile?9:11},color:txtColor,callback:v=>v+'%'}}
                }
            }
        });
    }

    applyKeuMonthRange();

    /* ══════════════════ GRAFIK #2 ══════════════════ */
    let dkTrendChartInst=null,dkBreakdownChartInst=null;
    let dkMode='month';const dkCache={};
    let dkJenisPilih='Dana Operasional',dkLastPayload=null;
    let dkJenisLastPayload = null;
    

    function updateDkTitle(mode,ctx){
        const el=document.getElementById('dkTitle');
        if(mode==='year'){const fY=document.getElementById('dkYearFrom').value,tY=document.getElementById('dkYearTo').value;el.textContent=fY===tY?`Dana Masuk vs Dana Keluar — ${fY}`:`Dana Masuk vs Dana Keluar — ${fY}–${tY}`;}
        else{const fM=parseInt(document.getElementById('dkMonthFrom').value),tM=parseInt(document.getElementById('dkMonthTo').value);el.textContent=fM===tM?`Dana Masuk vs Dana Keluar — ${BULAN_FULL[fM-1]} ${ctx.tahun}`:`Dana Masuk vs Dana Keluar — ${BULAN_FULL[fM-1]}–${BULAN_FULL[tM-1]} ${ctx.tahun}`;}
    }

    window.setDkMode=function(mode){
        window._dkModeCurrent = mode;
        dkMode=mode;
        ['Month','Year'].forEach(m=>{
            const active=m.toLowerCase()===mode;
            const btn=document.getElementById('btnDkMode'+m);
            btn.style.background=active?'var(--accent-muted)':'transparent';
            btn.style.color=active?'var(--accent-text)':'var(--text-secondary)';
            btn.style.fontWeight=active?'600':'400';
            document.getElementById('dkRange'+m).style.display=active?'flex':'none';
        });
        mode==='month'?applyDkMonthRange():applyDkYearRange();
    };

    async function fetchDkTahun(tahun){
        if(dkCache[tahun])return dkCache[tahun];
        const res=await fetch(`${KEU.debitKreditUrl}?tahun=${tahun}`,{headers:{'X-CSRF-TOKEN':KEU.csrfToken,'Accept':'application/json'}});
        dkCache[tahun]=await res.json();return dkCache[tahun];
    }
    function setDkLoading(on){
        document.getElementById('dkLoading').style.display=on?'block':'none';
        document.getElementById('dkTrendChart').style.opacity=on?'0.2':'1';
    }
    function updateDkBadge(text){const el=document.getElementById('dkLaporanBadge');if(el)el.textContent=text;}

    window.applyDkMonthRange=async function(){
        const tahun=parseInt(document.getElementById('dkMonthYear').value);
        let fromM=parseInt(document.getElementById('dkMonthFrom').value);
        let toM=parseInt(document.getElementById('dkMonthTo').value);
        if(fromM>toM){document.getElementById('dkMonthTo').value=fromM;toM=fromM;}
        setDkLoading(true);
        try{
            const data=await fetchDkTahun(tahun);const bl=data.bulanTerakhirLaporan??0;
            if(bl>0){toM=Math.min(toM,bl);if(fromM>bl)fromM=bl;document.getElementById('dkMonthTo').value=toM;document.getElementById('dkMonthFrom').value=fromM;}
            else{toM=fromM;document.getElementById('dkMonthTo').value=toM;}
            const fi=fromM-1,ti=toM-1;const sl=BULAN_LABEL.slice(fi,ti+1);
            updateDkTitle('month',{tahun});
            updateDkBadge(bl>0?`📋 Laporan s/d ${BULAN_FULL[bl-1]} ${tahun}`:'📋 Belum ada laporan');
            const sliced={labels:sl,debit:data.debit.slice(fi,ti+1),kredit:data.kredit.slice(fi,ti+1),total:(data.totalPerBulan||[]).slice(fi,ti+1),tahun,mode:'month'};
            buildDkTrendChart(sliced);buildDkTrendStats(sliced);
            const jpb={};
            (data.jenisLabels||[]).forEach(j=>{jpb[j]={saldoAwal:sl.map((_,i)=>data.jenisPerBulan?.[j]?.saldoAwal?.[fi+i]??0),debit:sl.map((_,i)=>data.jenisPerBulan?.[j]?.debit?.[fi+i]??0),kredit:sl.map((_,i)=>data.jenisPerBulan?.[j]?.kredit?.[fi+i]??0),total:sl.map((_,i)=>data.jenisPerBulan?.[j]?.total?.[fi+i]??0)};});
            buildDkJenisFilter(data.jenisLabels||[]);
            buildDkBreakdownChart({labels:sl,jenisLabels:data.jenisLabels||[],jenisPerBulan:jpb});
            dkJenisLastPayload = {labels:sl, jenisLabels:data.jenisLabels||[], jenisPerBulan:jpb};
            window._dkModeCurrent = 'month';
            window._keuCacheRef   = keuCache;
        }catch(e){console.error(e);}finally{setDkLoading(false);}
    };

    window.applyDkYearRange=async function(){
        let fromY=parseInt(document.getElementById('dkYearFrom').value);
        let toY=parseInt(document.getElementById('dkYearTo').value);
        if(fromY>toY){document.getElementById('dkYearTo').value=fromY;toY=fromY;}
        setDkLoading(true);
        try{
            const years=[];for(let y=fromY;y<=toY;y++)years.push(y);
            const allData=await Promise.all(years.map(fetchDkTahun));
            const labels=years.map(String);
            const debit=allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?d.debit.slice(0,bl).reduce((a,b)=>a+b,0):0;});
            const kredit=allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?d.kredit.slice(0,bl).reduce((a,b)=>a+b,0):0;});
            const total=allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?(d.totalPerBulan||[])[bl-1]??0:0;});
            updateDkTitle('year',{});
            const lastBl=allData[allData.length-1]?.bulanTerakhirLaporan??0;
            updateDkBadge(lastBl>0?`📋 Laporan s/d ${BULAN_FULL[lastBl-1]} ${toY}`:`📋 Belum ada laporan ${toY}`);
            buildDkTrendChart({labels,debit,kredit,total,mode:'year'});
            buildDkTrendStats({labels,debit,kredit,total,mode:'year'});
            const jl=allData[0]?.jenisLabels??[];const jpb={};
            jl.forEach(j=>{jpb[j]={
                saldoAwal:allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?((d.jenisPerBulan?.[j]?.saldoAwal||[])[0]??0):0;}),
                debit:allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?(d.jenisPerBulan?.[j]?.debit||[]).slice(0,bl).reduce((a,v)=>a+v,0):0;}),
                kredit:allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?(d.jenisPerBulan?.[j]?.kredit||[]).slice(0,bl).reduce((a,v)=>a+v,0):0;}),
                total:allData.map(d=>{const bl=d.bulanTerakhirLaporan??0;return bl>0?((d.jenisPerBulan?.[j]?.total||[])[bl-1]??0):0;}),
            };});
            buildDkJenisFilter(jl);buildDkBreakdownChart({labels,jenisLabels:jl,jenisPerBulan:jpb});
            dkJenisLastPayload = {labels, jenisLabels:jl, jenisPerBulan:jpb};
            window._dkModeCurrent = 'year';
            window._keuCacheRef   = keuCache;
        }catch(e){console.error(e);}finally{setDkLoading(false);}
    };

    function buildDkTrendChart(data){
        const ctx=document.getElementById('dkTrendChart');
        if(dkTrendChartInst)dkTrendChartInst.destroy();
        dkTrendChartInst=new Chart(ctx,{
            type:'bar',
            data:{labels:data.labels,datasets:[
                {type:'bar',label:'Masuk',data:data.debit,backgroundColor:isDark?'rgba(61,154,94,0.82)':'rgba(61,154,94,0.78)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'bar',label:'Keluar',data:data.kredit,backgroundColor:isDark?'rgba(226,75,74,0.82)':'rgba(226,75,74,0.78)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'line',label:'Saldo',data:data.total||[],borderColor:'#7f77dd',backgroundColor:'rgba(127,119,221,0.1)',borderWidth:2.5,pointRadius:isMobile?2:4,pointHoverRadius:6,pointBackgroundColor:'#7f77dd',pointBorderColor:isDark?'#ffffff':'#000000',pointBorderWidth:2,tension:0.35,fill:false,yAxisID:'yRight',order:1,
                  datalabels:{display:!isMobile,anchor:'end',align:'top',offset:4,color:'#7f77dd',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)}},
            ]},
            options:{
                responsive:true,maintainAspectRatio:false,layout:{padding:{top:isMobile?20:40}},
                interaction:{mode:'index',intersect:false},
                onClick(e){
                    const pts=dkTrendChartInst.getElementsAtEventForMode(e,'index',{intersect:false},true);
                    if(!pts.length)return;
                    const idx=pts[0].index;
                    const label=data.labels[idx];
                    if(window.showKasDetailModal && dkJenisLastPayload)
                        window.showKasDetailModal(label, {
                            debit:data.debit[idx]??0,
                            kredit:data.kredit[idx]??0,
                            total:data.total?.[idx]??0,
                        }, dkJenisLastPayload, idx);
                },
                plugins:{
                    legend:{display:false},
                    datalabels:{display:!isMobile&&(ctx=>ctx.dataset.type==='bar'),anchor:'end',align:'end',rotation:-90,offset:2,color:isDark?'#e5e7eb':'#374151',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)},
                    tooltip:{callbacks:{label:ctx=>` ${ctx.dataset.label}: ${fmtRp(ctx.parsed.y)}`}}
                },
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:isMobile,maxRotation:0,font:{size:isMobile?9:11}}},
                    yLeft:{type:'linear',position:'left',grid:{color:gridColor},beginAtZero:true,grace:'20%',ticks:{font:{size:isMobile?9:11},callback:v=>shortNominal(v)}},
                    yRight:{type:'linear',position:'right',grid:{drawOnChartArea:false},beginAtZero:true,grace:'20%',ticks:{font:{size:isMobile?9:11},color:'#7f77dd',callback:v=>shortNominal(v)}}
                }
            }
        });
    }

    function buildDkTrendStats(data){
        if(isMobile){document.getElementById('dkTrendStats').innerHTML='';return;}
        const td=(data.debit||[]).reduce((a,b)=>a+(b??0),0);
        const tk=(data.kredit||[]).reduce((a,b)=>a+(b??0),0);
        const saldo=data.total?.length?data.total[data.total.length-1]:0;
        document.getElementById('dkTrendStats').innerHTML=`
            <div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Total Dana Masuk</div><div style="font-size:16px;font-weight:600;color:#3d9a5e;">${fmtRp(td)}</div></div>
            <div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Total Dana Keluar</div><div style="font-size:16px;font-weight:600;color:#e24b4a;">${fmtRp(tk)}</div></div>
            <div><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px;">Saldo Akhir Periode</div><div style="font-size:16px;font-weight:600;color:#7f77dd;">${fmtRp(saldo)}</div></div>`;
    }

    function buildDkJenisFilter(jl){
        const sel=document.getElementById('dkJenisFilter');
        if(!jl.includes(dkJenisPilih))dkJenisPilih=jl[0]??'';
        sel.innerHTML=jl.map(j=>`<option value="${j}" ${j===dkJenisPilih?'selected':''}>${j}</option>`).join('');
    }
    window.changeDkJenis=function(){dkJenisPilih=document.getElementById('dkJenisFilter').value;if(dkLastPayload)buildDkBreakdownChart(dkLastPayload);};

    function buildDkBreakdownChart(payload){
        dkLastPayload=payload;
        const{labels,jenisPerBulan}=payload;
        const ctx=document.getElementById('dkBreakdownChart');
        if(dkBreakdownChartInst)dkBreakdownChartInst.destroy();
        const dj=jenisPerBulan[dkJenisPilih]||{debit:[],kredit:[],total:[]};
        const td=(dj.debit||[]).reduce((a,b)=>a+(b??0),0);
        const tk=(dj.kredit||[]).reduce((a,b)=>a+(b??0),0);
        const saldo=dj.total?.length?dj.total[dj.total.length-1]:0;
        if(!isMobile){
            document.getElementById('dkJenisStats').innerHTML=`
                <div style="background:rgba(61,154,94,.08);border:1px solid rgba(61,154,94,.2);border-radius:10px;padding:10px 12px;"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Total Dana Masuk</div><div style="font-size:15px;font-weight:600;color:#3d9a5e;">${fmtRp(td)}</div></div>
                <div style="background:rgba(226,75,74,.08);border:1px solid rgba(226,75,74,.2);border-radius:10px;padding:10px 12px;"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Total Dana Keluar</div><div style="font-size:15px;font-weight:600;color:#e24b4a;">${fmtRp(tk)}</div></div>
                <div style="background:rgba(127,119,221,.08);border:1px solid rgba(127,119,221,.2);border-radius:10px;padding:10px 12px;"><div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Saldo Akhir Periode</div><div style="font-size:15px;font-weight:600;color:#7f77dd;">${fmtRp(saldo)}</div></div>`;
        } else {
            document.getElementById('dkJenisStats').innerHTML='';
        }
        dkBreakdownChartInst=new Chart(ctx,{
            type:'bar',
            data:{labels,datasets:[
                {type:'bar',label:'Masuk',data:dj.debit||[],backgroundColor:'rgba(61,154,94,.8)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'bar',label:'Keluar',data:dj.kredit||[],backgroundColor:'rgba(226,75,74,.8)',borderRadius:4,borderSkipped:false,yAxisID:'yLeft',order:2},
                {type:'line',label:'Saldo',data:dj.total||[],borderColor:'#7f77dd',backgroundColor:'rgba(127,119,221,0.08)',borderWidth:2.5,pointRadius:isMobile?2:4,pointHoverRadius:6,pointBackgroundColor:'#7f77dd',pointBorderColor:isDark?'#ffffff':'#000000',pointBorderWidth:2,tension:0.35,fill:false,yAxisID:'yRight',order:1,
                  datalabels:{display:!isMobile,anchor:'end',align:'top',offset:4,color:'#7f77dd',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)}},
            ]},
            options:{
                responsive:true,maintainAspectRatio:false,layout:{padding:{top:isMobile?20:40}},
                interaction:{mode:'index',intersect:false},
                onClick(e){
                const pts=dkBreakdownChartInst.getElementsAtEventForMode(e,'nearest',{intersect:true},true);
                if(!pts.length)return;
                const pt=pts[0];
                /* hanya bar Kredit — ambil label dari datasets, bukan dari DOM element */
                const dsLabel = dkBreakdownChartInst.data.datasets[pt.datasetIndex]?.label ?? '';
                if(dsLabel !== 'Keluar') return;
                const idx=pt.index;
                const label=payload.labels[idx];
                if(window.showKasBreakdownDetail)
                    window.showKasBreakdownDetail(dkJenisPilih, label, idx);
            },

                plugins:{
                    legend:{display:true,position:'bottom',labels:{boxWidth:10,font:{size:11},color:isDark?'#cbd5e1':'#374151'}},
                    datalabels:{display:!isMobile&&(ctx=>ctx.dataset.type==='bar'),anchor:'end',align:'end',rotation:-90,offset:2,color:isDark?'#e5e7eb':'#374151',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)},
                    tooltip:{callbacks:{label:ctx=>` ${ctx.dataset.label}: ${fmtRp(ctx.parsed.y)}`}}
                },
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:isMobile,maxRotation:0,font:{size:isMobile?9:11}}},
                    yLeft:{type:'linear',position:'left',grid:{color:gridColor},beginAtZero:true,grace:'40%',ticks:{font:{size:isMobile?9:11},callback:v=>shortNominal(v)}},
                    yRight:{type:'linear',position:'right',grid:{drawOnChartArea:false},beginAtZero:true,grace:'40%',ticks:{font:{size:isMobile?9:11},color:'#7f77dd',callback:v=>shortNominal(v)}}
                }
            }
        });
    }

    applyDkMonthRange();

}); // end waitForChart
})();

/* ═══════════════════════════════════════════════════════════
   SUMMARY CARDS + FILTER (AJAX) + INSIGHT
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded',function(){runSummaryAnimation();generateInsights(initialDashboardData);});

function handleFilterChange(){
    const form=document.getElementById('filterForm');
    fetch(`${DASHBOARD_URL}?`+new URLSearchParams(new FormData(form)),{headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(res=>res.json()).then(data=>{
        const totalRow=data.totalRow||{total_pengajuan:0,total_aktual:0,total_dikembalikan:0,belum_dilaporkan:0};
        belumLaporData=data.belumLaporGrouped;
        const st=document.querySelectorAll('.summary-total'),sb=document.querySelectorAll('.summary-biaya');
        st[0].dataset.value=data.summary.totalPengajuan;st[1].dataset.value=data.summary.aktualBiaya;
        st[2].dataset.value=data.summary.belomLapor;st[3].dataset.value=data.summary.menunggu;
        st[4].dataset.value=data.summary.disetujui;st[5].dataset.value=data.summary.ditolak;
        document.querySelector('.summary-realisasi').dataset.value=data.summary.totalRealisasi;
        document.querySelector('.summary-belum').dataset.value=data.summary.totalBelumDilaporkan;
        sb[0].dataset.value=data.summary.totalBiaya;sb[1].dataset.value=data.summary.biayaMenunggu;
        sb[2].dataset.value=data.summary.biayaDisetujui;sb[3].dataset.value=data.summary.biayaDitolak;
        const persen=data.persenRealisasi??0;
        const pEl=document.querySelector('.summary-persen'),bEl=document.querySelector('.progress-bar');
        pEl.dataset.value=persen;bEl.dataset.value=persen;pEl.innerText='0%';bEl.style.width='0%';
        animatePercent(pEl,persen);animateProgressBar(bEl,Math.max(0,Math.min(persen,100)));
        const tb=document.getElementById('rincianTable');tb.innerHTML='';
        const tbl=document.querySelector('table'),of=tbl.querySelector('tfoot');if(of)of.remove();
        if(data.rincian.length===0){tb.innerHTML=`<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">Tidak ada data pada periode ini</td></tr>`;}
        else{
            data.rincian.forEach(row=>{
                const badge=row.kegiatan_belum_lapor==0?`<span style="padding:3px 8px;font-size:10px;font-weight:500;background:rgba(34,197,94,.1);color:#16A34A;border-radius:999px;">✓ Sudah Semua</span>`:
                    `<span onclick="showBelumLapor('${row.bidang.replace(/'/g,"\\'")}')"; style="padding:3px 8px;font-size:10px;font-weight:500;background:rgba(239,68,68,.1);color:#DC2626;border-radius:999px;cursor:pointer;">⚠ ${row.kegiatan_belum_lapor} kegiatan</span>`;
                const bc=row.belum_dilaporkan>0?'#DC2626':'#16A34A';
                tb.innerHTML+=`<tr class="dash-tr-hover"><td style="padding:9px 14px;font-weight:500;color:var(--text-primary);">${row.bidang}</td><td style="padding:9px 14px;text-align:right;color:var(--text-primary);">Rp ${Number(row.total_pengajuan).toLocaleString('id-ID')}</td><td style="padding:9px 14px;text-align:right;color:var(--text-primary);">Rp ${Number(row.total_aktual).toLocaleString('id-ID')}</td><td style="padding:9px 14px;text-align:right;color:var(--text-primary);">Rp ${Number(row.total_dikembalikan).toLocaleString('id-ID')}</td><td style="padding:9px 14px;text-align:center;">${badge}</td><td style="padding:9px 14px;text-align:right;font-weight:600;color:${bc};">Rp ${Number(row.belum_dilaporkan).toLocaleString('id-ID')}</td></tr>`;
            });
            const tf=document.createElement('tfoot');
            tf.innerHTML=`<tr style="background:var(--bg-elevated);font-weight:600;color:var(--text-primary);"><td style="padding:9px 14px;text-align:center;border-top:1px solid var(--border);">TOTAL</td><td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp ${Number(totalRow.total_pengajuan).toLocaleString('id-ID')}</td><td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp ${Number(totalRow.total_aktual).toLocaleString('id-ID')}</td><td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);">Rp ${Number(totalRow.total_dikembalikan).toLocaleString('id-ID')}</td><td style="border-top:1px solid var(--border);"></td><td style="padding:9px 14px;text-align:right;border-top:1px solid var(--border);color:#DC2626;">Rp ${Number(totalRow.belum_dilaporkan).toLocaleString('id-ID')}</td></tr>`;
            tbl.appendChild(tf);
        }
        document.getElementById('dashboardContent').style.opacity='1';runSummaryAnimation();
        const kpi=document.querySelectorAll('.kpi-indicator');
        function bKPI(g,p='color:#16A34A',n='color:#DC2626'){return g>0?{text:`▲ ${g}% vs periode sebelumnya`,style:`font-size:11px;font-weight:500;margin-top:8px;${p}`}:g<0?{text:`▼ ${Math.abs(g)}% vs periode sebelumnya`,style:`font-size:11px;font-weight:500;margin-top:8px;${n}`}:{text:'0% stabil',style:'font-size:11px;font-weight:500;margin-top:8px;color:var(--text-muted)'};}
        [bKPI(data.growth?.total??0),{text:'-',style:'font-size:11px;margin-top:8px;color:var(--text-muted)'},bKPI(data.growth?.approved??0),bKPI(data.growth?.rejected??0,'color:#DC2626','color:#16A34A')]
        .forEach((k,i)=>{if(!kpi[i])return;kpi[i].innerHTML=k.text;kpi[i].style.cssText=k.style;});
        generateInsights(data);
    });
    const bs=document.getElementById('filterForm').querySelector('[name="bulan"]').value;
    const ts=document.getElementById('tahunInput').value||'semua';
    let pt=bs==='semua'&&ts==='semua'?'Semua Data':bs==='semua'?'Semua Bulan • Tahun '+ts:ts==='semua'?new Date(0,bs-1).toLocaleString('id-ID',{month:'long'})+' • Semua Tahun':new Date(0,bs-1).toLocaleString('id-ID',{month:'long'})+' '+ts;
    document.getElementById('periodeBadge').innerText='📊 '+pt;
}
window.handleFilterChange = handleFilterChange;

/* ═══════════════════════════════════════════════════════════
   MODAL BELUM LAPOR
═══════════════════════════════════════════════════════════ */
function formatRupiah(angka){return new Intl.NumberFormat('id-ID').format(angka);}
function showBelumLapor(bidang){
    if(!belumLaporData[bidang]||belumLaporData[bidang].length===0){Swal.fire({icon:'success',title:'Sudah Lengkap',text:'Semua kegiatan sudah membuat laporan.',confirmButtonColor:'#16a34a'});return;}
    let html=`<div class="text-left text-sm">`;
    belumLaporData[bidang].forEach((item,i)=>{
        const isPembuat=String(item.nama??'').trim().toLowerCase()===String(DASH_USERNAME??'').trim().toLowerCase();
        const laporanLocked=Boolean(item.laporan_locked);const canBuat=isPembuat&&!laporanLocked;
        html+=`<div style="padding:10px 0;border-bottom:1px solid #eee;"><div style="font-weight:600;">${i+1}. PIC : ${item.nama??'-'}</div><div style="color:#555;">Keperluan: ${item.keperluan??'-'}</div><div style="color:#dc2626;font-weight:500;">Biaya: Rp ${formatRupiah(item.biaya)}</div>${canBuat&&item.id?`<button onclick="Swal.close();window.openDashBuatLaporan('${item.id}','${item.biaya}')" style="margin-top:6px;padding:4px 12px;border-radius:7px;border:none;background:#2563eb;color:#fff;font-size:12px;cursor:pointer;font-family:inherit;font-weight:500;">📝 Buat Laporan</button>`:''}</div>`;
    });
    html+=`</div>`;
    Swal.fire({title:'Kegiatan Yang Belum Dilaporkan',html,width:600,confirmButtonText:'Tutup',confirmButtonColor:'#2563eb'});
}
window.showBelumLapor = showBelumLapor;

document.addEventListener('DOMContentLoaded', function() {
    const tahunInput=document.getElementById('tahunInput');
    if(tahunInput)tahunInput.addEventListener('input',function(){this.value=this.value.replace(/[^0-9]/g,'').slice(0,4);});
});

/* ═══════════════════════════════════════════════════════════
   ANIMASI COUNTER / PROGRESS BAR
═══════════════════════════════════════════════════════════ */
function animateCounter(el,end,duration=800){let s=null;function a(t){if(!s)s=t;const p=t-s,v=Math.min(p/duration*end,end);el.innerText=Math.floor(v).toLocaleString('id-ID');if(p<duration)requestAnimationFrame(a);else el.innerText=end.toLocaleString('id-ID');}requestAnimationFrame(a);}
function animatePercent(el,end,duration=800){let s=null;function a(t){if(!s)s=t;const p=t-s;el.innerText=Math.floor(Math.min(p/duration*end,end))+'%';if(p<duration)requestAnimationFrame(a);else el.innerText=end+'%';}requestAnimationFrame(a);}
function animateRupiah(el,end,duration=800){let s=null;function a(t){if(!s)s=t;const p=t-s,v=Math.min(p/duration*end,end);el.innerText='Rp '+Math.floor(v).toLocaleString('id-ID');if(p<duration)requestAnimationFrame(a);}requestAnimationFrame(a);}
function animateProgressBar(el,rv){setTimeout(()=>{el.style.width=Math.min(rv,100)+'%';},100);}
function runSummaryAnimation(){
    document.querySelectorAll('.summary-total').forEach(el=>animateCounter(el,parseInt(el.dataset.value)||0));
    document.querySelectorAll('.rupiah').forEach(el=>animateRupiah(el,parseInt(el.dataset.value)||0));
    document.querySelectorAll('.summary-persen').forEach(el=>animatePercent(el,parseFloat(el.dataset.value)||0));
    document.querySelectorAll('.progress-bar').forEach(el=>animateProgressBar(el,parseFloat(el.dataset.value)||0));
}
document.addEventListener('DOMContentLoaded',runSummaryAnimation);

/* ═══════════════════════════════════════════════════════════
   INSIGHT OTOMATIS
═══════════════════════════════════════════════════════════ */
function generateInsights(data){
    const list=document.getElementById('insightList');if(!list)return;list.innerHTML='';const ins=[];
    const persen=Number(data.persenRealisasi??0),s=data.summary??{};
    const tp=Number(s.totalPengajuan??0),dit=Number(s.ditolak??0),men=Number(s.menunggu??0),bl=Number(s.belomLapor??0);
    if(persen<50&&tp>0)ins.push('Realisasi dana masih di bawah 50%. Perlu percepatan eksekusi kegiatan.');
    if(persen>=90&&persen===tp)ins.push('Realisasi dana sudah sangat baik. Serapan anggaran tinggi.');
    if(dit>0)ins.push('Tingkat penolakan tinggi. Evaluasi kualitas pengajuan kegiatan.');
    if(men>3)ins.push('Banyak pengajuan masih menunggu persetujuan. Potensi bottleneck approval.');
    if(bl>0)ins.push(`Masih ada ${bl} kegiatan yang belum melaporkan dana. Perlu follow-up.`);
    if(ins.length===0)ins.push('Semua indikator dashboard dalam kondisi sehat.');
    ins.forEach(text=>{const li=document.createElement('li');li.textContent='• '+text;list.appendChild(li);});
}

/* ═══════════════════════════════════════════════════════════
   MODAL DETAIL BIDANG
═══════════════════════════════════════════════════════════ */
(function(){
    'use strict';
    const DETAIL_URL=DETAIL_BIDANG_URL;
    const CSRF=CSRF_TOKEN;const PER_PAGE=20;
    const BF=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    let bdAllRows=[],bdFiltered=[],bdPage=1,bdCurrentTitle='';
    function fmtRp(n){return 'Rp '+Number(n||0).toLocaleString('id-ID');}
    function fmtTgl(str){if(!str)return '—';const d=new Date(str);if(isNaN(d))return str;return d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});}
    function badgeClass(s){if(!s)return 'bd-badge-pengajuan';const l=s.toLowerCase();if(l.includes('koperasi'))return 'bd-badge-koperasi';if(l.includes('insentif'))return 'bd-badge-insentif';return 'bd-badge-pengajuan';}
    function openModal(){document.getElementById('bidangDetailModal').style.display='flex';document.body.style.overflow='hidden';}
    window.closeBidangModal=function(){document.getElementById('bidangDetailModal').style.display='none';document.body.style.overflow='';bdAllRows=[];bdFiltered=[];bdPage=1;const s=document.getElementById('bdSearch');if(s)s.value='';};
    document.getElementById('bidangDetailModal')?.addEventListener('click',function(e){if(e.target===this)window.closeBidangModal();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.getElementById('bidangDetailModal')?.style.display==='flex')window.closeBidangModal();});
    function renderPage(){
        const tbody=document.getElementById('bdTbody'),tfoot=document.getElementById('bdTfoot');
        const emptyEl=document.getElementById('bdEmpty'),tableEl=document.getElementById('bdTable');
        const start=(bdPage-1)*PER_PAGE,end=Math.min(start+PER_PAGE,bdFiltered.length),page=bdFiltered.slice(start,end);
        tbody.innerHTML='';tfoot.innerHTML='';
        if(!page.length){emptyEl.style.display='block';tableEl.style.display='none';document.getElementById('bdCount').textContent='';document.getElementById('bdPagerInfo').textContent='';document.getElementById('bdPager').innerHTML='';return;}
        emptyEl.style.display='none';tableEl.style.display='';
        const frag=document.createDocumentFragment();
        page.forEach((row,i)=>{const tr=document.createElement('tr');tr.innerHTML=`<td style="text-align:center;color:var(--text-muted);font-size:10.5px;">${start+i+1}</td><td style="font-weight:500;color:var(--text-primary);max-width:160px;overflow:hidden;text-overflow:ellipsis;">${row.nama??'—'}</td><td>${row.tujuan??'—'}</td><td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;">${row.keperluan??'—'}</td><td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;font-weight:600;color:var(--text-primary);">${fmtRp(row.realisasi)}</td><td style="text-align:center;color:var(--text-muted);font-size:11px;">${fmtTgl(row.tanggal)}</td><td style="text-align:center;"><span class="bd-badge ${badgeClass(row.sumber)}">${row.sumber??'—'}</span></td>`;frag.appendChild(tr);});
        tbody.appendChild(frag);
        const tr=bdFiltered.reduce((s,r)=>s+Number(r.realisasi||0),0);
        tfoot.innerHTML=`<tr><td colspan="4" style="padding:9px 12px;font-size:11px;color:var(--text-muted);">Total (${bdFiltered.length.toLocaleString('id-ID')} baris)</td><td style="padding:9px 12px;text-align:right;font-family:'DM Mono',monospace;color:var(--accent-text);">${fmtRp(tr)}</td><td colspan="2"></td></tr>`;
        document.getElementById('bdCount').textContent=`${bdFiltered.length.toLocaleString('id-ID')} data`;
        document.getElementById('bdPagerInfo').textContent=`${start+1}–${end} dari ${bdFiltered.length.toLocaleString('id-ID')}`;
        buildPager();
    }
    function buildPager(){
        const total=Math.ceil(bdFiltered.length/PER_PAGE),pager=document.getElementById('bdPager');
        pager.innerHTML='';if(total<=1)return;
        const make=(label,page,active=false,disabled=false)=>{const b=document.createElement('button');b.innerHTML=label;if(active)b.classList.add('active');if(disabled)b.disabled=true;b.onclick=()=>{bdPage=page;renderPage();};pager.appendChild(b);};
        make('‹',bdPage-1,false,bdPage===1);
        const MAX=5;let s=Math.max(1,bdPage-Math.floor(MAX/2)),e=Math.min(total,s+MAX-1);
        if(e-s<MAX-1)s=Math.max(1,e-MAX+1);
        if(s>1){make(1,1);if(s>2){const sp=document.createElement('span');sp.textContent='…';sp.style.cssText='padding:0 4px;color:var(--text-muted);font-size:11px;';pager.appendChild(sp);}}
        for(let p=s;p<=e;p++)make(p,p,p===bdPage);
        if(e<total){if(e<total-1){const sp=document.createElement('span');sp.textContent='…';sp.style.cssText='padding:0 4px;color:var(--text-muted);font-size:11px;';pager.appendChild(sp);}make(total,total);}
        make('›',bdPage+1,false,bdPage===total);
    }
    window.bdFilterTable=function(){const q=(document.getElementById('bdSearch')?.value??'').toLowerCase().trim();bdFiltered=q?bdAllRows.filter(r=>(r.nama??'').toLowerCase().includes(q)||(r.keperluan??'').toLowerCase().includes(q)||(r.tujuan??'').toLowerCase().includes(q)||(r.sumber??'').toLowerCase().includes(q)):[...bdAllRows];bdPage=1;renderPage();};
    window.bdExportCSV=function(){if(!bdFiltered.length)return;const h=['No','Nama','Tujuan','Keperluan','Realisasi','Tanggal','Sumber'];const rows=bdFiltered.map((r,i)=>[i+1,r.nama??'',r.tujuan??'',r.keperluan??'',r.realisasi??0,r.tanggal??'',r.sumber??''].map(v=>`"${String(v).replace(/"/g,'""')}"`).join(','));const csv=[h.join(','),...rows].join('\n');const blob=new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'});const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=`${bdCurrentTitle.replace(/\s+/g,'_')}.csv`;a.click();URL.revokeObjectURL(url);};
    window.showBidangDetail=async function(bidang,tahun,bulan){
        const disp={'SEKRETARIS':'Sekretaris','BIDANG 1':'Bidang 1','BIDANG 2':'Bidang 2','BIDANG 3':'Bidang 3','BIDANG 4':'Bidang 4','BIDANG 5':'Bidang 5','BIDANG 6':'Bidang 6','GM':'GM','KORLAP':'Korlap'};
        let subtitle=disp[bidang]??bidang;if(bulan&&bulan!=='semua')subtitle+=' · '+(BF[parseInt(bulan)-1]??bulan);subtitle+=' · '+tahun;
        bdCurrentTitle=`Detail_${disp[bidang]??bidang}_${tahun}`;
        document.getElementById('bdModalTitle').textContent=`Detail — ${disp[bidang]??bidang}`;
        document.getElementById('bdModalSubtitle').textContent=subtitle;
        bdAllRows=[];bdFiltered=[];bdPage=1;const s=document.getElementById('bdSearch');if(s)s.value='';
        document.getElementById('bdTbody').innerHTML='';document.getElementById('bdTfoot').innerHTML='';document.getElementById('bdPager').innerHTML='';document.getElementById('bdPagerInfo').textContent='';document.getElementById('bdCount').textContent='';
        document.getElementById('bdLoading').style.display='flex';document.getElementById('bdEmpty').style.display='none';document.getElementById('bdTable').style.display='none';
        openModal();
        try{const qs=new URLSearchParams({bidang,tahun,bulan:bulan??'semua'});const res=await fetch(`${DETAIL_URL}?${qs}`,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});const json=await res.json();bdAllRows=json.data??[];bdFiltered=[...bdAllRows];}
        catch(e){console.error(e);}finally{document.getElementById('bdLoading').style.display='none';renderPage();}
    };
})();

document.addEventListener('DOMContentLoaded',()=>{
    ['bidangDetailModal','dashModalPilihLaporan','dashModalUploadLaporan','kasDetailModal'].forEach(id=>{const el=document.getElementById(id);if(el)document.body.appendChild(el);});
});

/* ═══════════════════════════════════════════════════════════
   MODAL RINGKASAN KAS (klik chart Dana Masuk vs Dana Keluar)
═══════════════════════════════════════════════════════════ */
(function(){
'use strict';
 
/* ─── helpers ─── */
function fmtRp(n){ return 'Rp '+Number(n||0).toLocaleString('id-ID'); }
 
const BIDANG_DISPLAY_KAS = {
    'SEKRETARIS':'Sekretaris','BIDANG 1':'Bidang 1','BIDANG 2':'Bidang 2',
    'BIDANG 3':'Bidang 3','BIDANG 4':'Bidang 4','BIDANG 5':'Bidang 5',
    'BIDANG 6':'Bidang 6','GM':'GM','KORLAP':'Korlap'
};
const BIDANG_TETAP_KAS = [
    'SEKRETARIS','BIDANG 1','BIDANG 2','BIDANG 3',
    'BIDANG 4','BIDANG 5','BIDANG 6','GM','KORLAP'
];
const JENIS_BIDANG_MAP = {
    'Dana Perjuangan'     :'DANA PERJUANGAN',
    'Dana Sosial'         :'DANA SOSIAL',
    'Dana Permusyawaratan':'DANA PERMUSYAWARATAN',
    'Dana Sosial Politik' :'DANA SOSIAL POLITIK',
    'Bunga Giro'          :'BUNGA GIRO',
    'Bunga Deposito'      :'BUNGA DEPOSITO',
};
 
/* ─── open / close ─── */
function openKasModal(){
    document.getElementById('kasDetailModal').style.display='flex';
    document.body.style.overflow='hidden';
}
window.closeKasModal=function(){
    document.getElementById('kasDetailModal').style.display='none';
    document.body.style.overflow='';
};
document.getElementById('kasDetailModal')?.addEventListener('click',function(e){
    if(e.target===this) window.closeKasModal();
});
document.addEventListener('keydown',e=>{
    if(e.key==='Escape' && document.getElementById('kasDetailModal')?.style.display==='flex')
        window.closeKasModal();
});
 
/* ─── helpers render ─── */
function kasShowLoading(on){
    document.getElementById('kasModalLoading').style.display = on ? 'block' : 'none';
    document.getElementById('kasModalTable').style.display   = on ? 'none'  : '';
    document.getElementById('kasModalEmpty').style.display   = 'none';
}
function kasShowEmpty(){
    document.getElementById('kasModalEmpty').style.display   = 'block';
    document.getElementById('kasModalTable').style.display   = 'none';
    document.getElementById('kasModalLoading').style.display = 'none';
}
 
/* ─── set header 4 kolom (Ringkasan Kas) ─── */
function setKasHeader4(){
    document.getElementById('kasModalThead').innerHTML=`
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:left;">Jenis Kas</th>
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Saldo Awal</th>
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Dana Masuk</th>
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Dana Keluar</th>
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Saldo Akhir</th>`;
}
 
/* ─── set header 2 kolom (Dana Operasional per Bidang) ─── */
function setKasHeader2(){
    document.getElementById('kasModalThead').innerHTML=`
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:left;">Bidang</th>
        <th style="padding:9px 14px;font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);text-align:right;">Realisasi</th>`;
}
 
/* ════════════════════════════════════════
   showKasDetailModal
   Klik bar/line di dkTrendChart → semua jenis kas
════════════════════════════════════════ */
window.showKasDetailModal = function(periodLabel, totals, jenisPayload, idx){
    setKasHeader4();
    document.getElementById('kasModalTitle').textContent    = 'Ringkasan Kas';
    document.getElementById('kasModalSubtitle').textContent = periodLabel;
 
    const tbody = document.getElementById('kasModalTbody');
    const tfoot = document.getElementById('kasModalTfoot');
    tbody.innerHTML=''; tfoot.innerHTML='';
 
    if(!jenisPayload?.jenisLabels?.length){
        kasShowEmpty(); openKasModal(); return;
    }
 
    let grandSa=0, grandD=0, grandK=0, grandT=0;
    jenisPayload.jenisLabels.forEach(j=>{
        const jd = jenisPayload.jenisPerBulan[j];
        if(!jd) return;
        const sa = jd.saldoAwal?.[idx] ?? 0;
        const d = jd.debit?.[idx]  ?? 0;
        const k = jd.kredit?.[idx] ?? 0;
        const t = jd.total?.[idx]  ?? 0;
        grandSa+=sa; grandD+=d; grandK+=k; grandT+=t;
        const tr=document.createElement('tr');
        tr.innerHTML=`
            <td style="font-weight:500;color:var(--text-primary);">${j}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;color:var(--text-secondary);">${fmtRp(sa)}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;color:#3d9a5e;">${fmtRp(d)}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;color:#e24b4a;">${fmtRp(k)}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;font-weight:600;color:#7f77dd;">${fmtRp(t)}</td>`;
        tbody.appendChild(tr);
    });
 
    tfoot.innerHTML=`<tr>
        <td style="padding:9px 14px;font-size:11px;color:var(--text-muted);">Total</td>
        <td style="padding:9px 14px;text-align:right;font-family:'DM Mono',monospace;color:var(--text-secondary);font-weight:600;">${fmtRp(grandSa)}</td>
        <td style="padding:9px 14px;text-align:right;font-family:'DM Mono',monospace;color:#3d9a5e;font-weight:600;">${fmtRp(grandD)}</td>
        <td style="padding:9px 14px;text-align:right;font-family:'DM Mono',monospace;color:#e24b4a;font-weight:600;">${fmtRp(grandK)}</td>
        <td style="padding:9px 14px;text-align:right;font-family:'DM Mono',monospace;color:#7f77dd;font-weight:600;">${fmtRp(grandT)}</td>
    </tr>`;
 
    kasShowLoading(false);
    openKasModal();
};
 
/* ════════════════════════════════════════
   showKasBreakdownDetail
   Klik bar KREDIT di dkBreakdownChart
   Dana Operasional → summary per bidang (kasDetailModal)
   Lainnya          → bidangDetailModal (API detail)
════════════════════════════════════════ */
window.showKasBreakdownDetail = function(jenis, periodLabel, idx){
    if(jenis === 'Dana Operasional'){
        _showDanaOperasionalModal(periodLabel, idx);
        return;
    }
 
    /* Jenis lain → pakai bidangDetailModal */
    const mode = window._dkModeCurrent ?? 'month';
    let tahun, bulan;
    if(mode === 'month'){
        tahun = document.getElementById('dkMonthYear').value;
        bulan = parseInt(document.getElementById('dkMonthFrom').value) + idx;
    } else {
        tahun = parseInt(document.getElementById('dkYearFrom').value) + idx;
        bulan = 'semua';
    }
    window.showBidangDetail(JENIS_BIDANG_MAP[jenis] ?? jenis.toUpperCase(), tahun, bulan);
};
 
/* ────────────────────────────────────────
   Dana Operasional: realisasi per bidang
──────────────────────────────────────── */
function _showDanaOperasionalModal(periodLabel, idx){
    setKasHeader2();
    document.getElementById('kasModalTitle').textContent    = 'Dana Operasional — Per Bidang';
    document.getElementById('kasModalSubtitle').textContent = periodLabel;
 
    const tbody = document.getElementById('kasModalTbody');
    const tfoot = document.getElementById('kasModalTfoot');
    tbody.innerHTML=''; tfoot.innerHTML='';
    kasShowLoading(true);
    openKasModal();
 
    const mode       = window._dkModeCurrent ?? 'month';
    const cacheRef   = window._keuCacheRef   ?? {};
    const trenKeuUrl = KEU.trenKeuUrl;
    const csrfToken  = KEU.csrfToken;
 
    let tahun, bulanIdx; /* bulanIdx: 0-based index di array bulanan */
    if(mode === 'month'){
        tahun    = parseInt(document.getElementById('dkMonthYear').value);
        bulanIdx = (parseInt(document.getElementById('dkMonthFrom').value) - 1) + idx;
    } else {
        tahun    = parseInt(document.getElementById('dkYearFrom').value) + idx;
        bulanIdx = -1; /* semua bulan dijumlah */
    }
 
    (async ()=>{
        try{
            let data = cacheRef[tahun];
            if(!data){
                const res = await fetch(`${trenKeuUrl}?tahun=${tahun}`,
                    {headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}});
                data = await res.json();
                cacheRef[tahun] = data;
            }
 
            let grandTotal = 0;
            const rows = BIDANG_TETAP_KAS.map(b=>{
                const bpb   = data.bidangPerBulan?.[b] ?? [];
                const total = bulanIdx >= 0
                    ? (bpb[bulanIdx] ?? 0)
                    : bpb.reduce((a,v)=>a+(v??0), 0);
                grandTotal += total;
                return { bidang: BIDANG_DISPLAY_KAS[b]??b, total };
            });
 
            kasShowLoading(false);
            if(!rows.length){ kasShowEmpty(); return; }
 
            rows.forEach(r=>{
                const tr=document.createElement('tr');
                tr.innerHTML=`
                    <td style="font-weight:500;color:var(--text-primary);">${r.bidang}</td>
                    <td style="text-align:right;font-family:'DM Mono',monospace;font-size:11px;font-weight:600;color:var(--text-primary);">${fmtRp(r.total)}</td>`;
                tbody.appendChild(tr);
            });
 
            tfoot.innerHTML=`<tr>
                <td style="padding:9px 14px;font-size:11px;color:var(--text-muted);">Total</td>
                <td style="padding:9px 14px;text-align:right;font-family:'DM Mono',monospace;font-weight:600;color:var(--accent-text);">${fmtRp(grandTotal)}</td>
            </tr>`;
 
        } catch(err){
            console.error('kas modal error:', err);
            kasShowLoading(false);
            kasShowEmpty();
        }
    })();
}
 
})();

/* ═══════════════════════════════════════════════════════════
   MODAL: PILIH JENIS LAPORAN & UPLOAD LAPORAN
═══════════════════════════════════════════════════════════ */
(function(){
    'use strict';
    let dashLaporanTargetId=null,dashLaporanBiaya=0;
    function openDashModal(id){const m=document.getElementById(id);if(!m)return;m.classList.add('open');document.body.style.overflow='hidden';}
    window.closeDashModal=function(id){const m=document.getElementById(id);if(!m)return;m.classList.remove('open');if(!document.querySelector('.modal-backdrop.open'))document.body.style.overflow='';};
    function showDashToast(msg,ok=true){const t=document.getElementById('dash-toast');if(!t)return;t.textContent=msg;t.style.background=ok?'#111827':'#7F1D1D';t.style.opacity='1';t.style.transform='translateY(0)';clearTimeout(t._t);t._t=setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(8px)';},3000);}
    window.dashApplyRibuan=function(input){const from=input.value.length-input.selectionStart;input.value=String(input.value).replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');const pos=input.value.length-from;input.setSelectionRange(pos,pos);};
    function parseRibuan(val){return String(val||'').replace(/\./g,'');}
    function formatRibuan(val){return String(val||'').replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
    window.dashHitungSelisih=function(){const aktual=Number(parseRibuan(document.getElementById('dashLaporanAktualBiaya').value)||0);const box=document.getElementById('dashLaporanSelisihBox');if(!dashLaporanBiaya||!aktual){box.style.display='none';return;}box.style.display='block';const diff=dashLaporanBiaya-aktual;box.innerHTML=diff>0?`<span style="color:#16A34A;font-weight:500;">Sisa Dana: Rp ${formatRibuan(diff)}</span>`:diff<0?`<span style="color:#DC2626;font-weight:500;">Kekurangan: Rp ${formatRibuan(Math.abs(diff))}</span>`:`<span style="color:var(--accent);font-weight:500;">Dana Pas ✓</span>`;};
    ['dashModalPilihLaporan','dashModalUploadLaporan'].forEach(id=>{document.getElementById(id)?.addEventListener('click',function(e){if(e.target===this)window.closeDashModal(id);});});
    document.addEventListener('keydown',e=>{if(e.key!=='Escape')return;window.closeDashModal('dashModalPilihLaporan');window.closeDashModal('dashModalUploadLaporan');});
    window.openDashBuatLaporan=function(pengajuanId,biaya){dashLaporanTargetId=String(pengajuanId);dashLaporanBiaya=Number(biaya||0);openDashModal('dashModalPilihLaporan');};
    document.getElementById('dashBtnInputManual').onclick=function(){if(!dashLaporanTargetId)return;window.location.href=`/laporan/manual/${dashLaporanTargetId}`;};
    document.getElementById('dashBtnUploadPDF').onclick=function(){if(!dashLaporanTargetId)return;document.getElementById('dashLaporanTargetId').value=dashLaporanTargetId;document.getElementById('dashLaporanBiayaPengajuan').value=formatRibuan(String(dashLaporanBiaya));document.getElementById('dashLaporanAktualBiaya').value='';document.getElementById('dashLaporanSelisihBox').style.display='none';document.getElementById('dashLaporanFileInput').value='';window.closeDashModal('dashModalPilihLaporan');openDashModal('dashModalUploadLaporan');};
    document.getElementById('dashConfirmUpload').onclick=async function(){
        const file=document.getElementById('dashLaporanFileInput').files[0];
        const aktual=parseRibuan(document.getElementById('dashLaporanAktualBiaya').value);
        const id=document.getElementById('dashLaporanTargetId').value;
        if(!file)return showDashToast('Pilih file laporan',false);if(!aktual)return showDashToast('Isi aktual biaya',false);if(!id)return showDashToast('ID tidak ditemukan',false);
        if(file.type!=='application/pdf')return showDashToast('Hanya PDF',false);if(file.size>5*1024*1024)return showDashToast('Maksimal 5MB',false);
        const btn=document.getElementById('dashConfirmUpload'),txt=document.getElementById('dashUploadText'),spn=document.getElementById('dashUploadSpinner');
        btn.disabled=true;txt.textContent='Mengunggah…';spn.style.display='block';
        try{const fd=new FormData();fd.append('ID',id);fd.append('LampiranLaporan',file);fd.append('AktualBiaya',aktual);const res=await fetch(UPLOAD_LAPORAN_URL,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF_TOKEN,'Accept':'application/json'},body:fd});const json=await res.json();if(json.success){showDashToast('Laporan berhasil diupload ✅');window.closeDashModal('dashModalUploadLaporan');document.getElementById('dashLaporanFileInput').value='';}else{showDashToast(json.message||'Gagal upload',false);}}
        catch{showDashToast('Kesalahan jaringan',false);}
        finally{btn.disabled=false;txt.textContent='Upload';spn.style.display='none';}
    };
})();