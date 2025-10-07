import axios from 'axios';

function sanitize(t){ const d=document.createElement('div'); d.innerText=String(t??''); return d.innerHTML; }
function toast(msg,variant='success'){
  const wrap=document.getElementById('toasts');
  const id='t'+Date.now();
  const el=document.createElement('div');
  el.className='toast align-items-center text-bg-'+(variant==='success'?'success':'danger')+' border-0 show';
  el.id=id; el.role='alert'; el.ariaLive='assertive'; el.ariaAtomic='true';
  el.innerHTML=`<div class="d-flex"><div class="toast-body">${sanitize(msg)}</div>
  <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  wrap.appendChild(el); setTimeout(()=>el.remove(),2500);
}

const tbody=document.getElementById('users-tbody');
const pager=document.getElementById('pager');
const form=document.getElementById('search-form');

async function fetchUsers(params={}){
  const {data}=await axios.get('/api/v1/admin/users',{params});
  return data?.data;
}

function render(users){
  tbody.innerHTML='';
  users.data.forEach((u,i)=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${users.from+i}</td>
      <td>${sanitize(u.email)}</td>
      <td>${sanitize(u.type)}</td>
      <td>${sanitize(u.status||'active')}</td>
      <td>
        <button class="btn btn-sm btn-outline-warning" data-act="toggle" data-id="${u.id}">تفعيل/تعطيل</button>
      </td>`;
    tbody.appendChild(tr);
  });
  // pager
  pager.innerHTML='';
  const prev=`<li class="page-item ${users.prev_page_url?'':'disabled'}"><a class="page-link" href="#" data-page="${users.current_page-1}">السابق</a></li>`;
  pager.insertAdjacentHTML('beforeend', prev);
  for(let p=1;p<=users.last_page;p++){
    pager.insertAdjacentHTML('beforeend', `<li class="page-item ${p===users.current_page?'active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`);
  }
  const next=`<li class="page-item ${users.next_page_url?'':'disabled'}"><a class="page-link" href="#" data-page="${users.current_page+1}">التالي</a></li>`;
  pager.insertAdjacentHTML('beforeend', next);
}

async function load(page=1,q=''){
  try{
    const data=await fetchUsers({page, q});
    render(data);
  }catch(e){
    console.error(e);
    toast('فشل تحميل المستخدمين','danger');
  }
}

pager.addEventListener('click', (e)=>{
  const a=e.target.closest('a[data-page]');
  if(!a) return; e.preventDefault();
  const p=parseInt(a.dataset.page,10); if(!isNaN(p)) load(p, form.q.value.trim());
});

tbody.addEventListener('click', async (e)=>{
  const btn=e.target.closest('[data-act="toggle"]');
  if(!btn) return; const id=btn.dataset.id;
  if(!confirm('تأكيد تغيير الحالة؟')) return;
  try{
    await axios.patch(`/api/v1/admin/users/${id}/status`);
    toast('تم التحديث'); load(pager.querySelector('.active a')?.dataset.page||1, form.q.value.trim());
  }catch(err){ console.error(err); toast('فشل التحديث','danger'); }
});

form.addEventListener('submit', (e)=>{ e.preventDefault(); load(1, form.q.value.trim()); });

load();

