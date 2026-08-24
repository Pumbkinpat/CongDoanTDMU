// Public Portal Enterprise Logic connected directly to REST API Backend
let currentArticles = [];

document.addEventListener('DOMContentLoaded', () => {
  loadArticles('all');
  loadUpcomingEvents();
  setupFilterTabs();
  setupSearch();
  setupModal();
});

async function loadArticles(category = 'all', search = '') {
  const container = document.getElementById('article_container');
  if (container) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
        <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color: var(--primary-color);"></i>
        <p style="margin-top: 10px; color: var(--text-muted);">Đang kết nối CSDL và tải tin tức mới nhất...</p>
      </div>
    `;
  }

  try {
    const res = await API.getArticles(category, 'published', search);
    if (res.success) {
      currentArticles = res.data;
      renderArticles(currentArticles);
      if (currentArticles.length > 0 && category === 'all' && !search) {
        updateHeroBanner(currentArticles[0]);
      }
    }
  } catch (err) {
    console.error("Error loading articles:", err);
  }
}

function updateHeroBanner(art) {
  if (!art) return;
  const titleEl = document.querySelector('.hero-title');
  const metaEl = document.querySelector('.hero-meta');
  const imgEl = document.querySelector('.hero-image');

  if (titleEl) titleEl.innerText = art.title;
  if (imgEl && art.image) imgEl.src = art.image;
  if (metaEl) {
    metaEl.innerHTML = `
      <span><i class="fa-regular fa-calendar"></i> ${art.createdAt || '2026-08-21'}</span>
      <span><i class="fa-solid fa-user-pen"></i> ${art.author || 'Ban Thường Vụ Công Đoàn'}</span>
      <span><i class="fa-regular fa-eye"></i> ${art.viewsCount || 0} lượt xem</span>
    `;
  }
}

function renderArticles(articles) {
  const container = document.getElementById('article_container');
  if (!container) return;

  if (articles.length === 0) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px; background: white; border-radius: 12px; border: 1px solid #E2E8F0;">
        <i class="fa-solid fa-folder-open fa-2x" style="color: #94A3B8;"></i>
        <p style="margin-top: 12px; color: #64748B; font-weight: 600;">Chưa có bài viết nào thuộc chuyên mục này.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = articles.map(art => `
    <article class="card" id="article_card_${art.id}">
      <img src="${art.image || 'images/banner.jpg'}" alt="${art.title}" class="card-img">
      <div class="card-body">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span class="badge badge-gold">${art.categoryName}</span>
          ${art.isAiGenerated ? '<span class="badge badge-info" style="font-size: 10px;"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Created</span>' : ''}
        </div>
        <h3 class="card-title">${art.title}</h3>
        <p class="card-excerpt">${art.summary || ''}</p>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <button class="btn btn-outline btn-sm" onclick="openArticleModal(${art.id})" id="btn_view_${art.id}">
            <i class="fa-regular fa-eye"></i> Đọc Tin & AI Summary
          </button>
          
          <button class="btn btn-outline btn-sm" style="color: var(--primary-color);" onclick="likeArticle(${art.id})" id="btn_like_${art.id}">
            <i class="fa-regular fa-thumbs-up"></i> <span id="like_count_${art.id}">${art.likesCount || 0}</span>
          </button>
        </div>

        <div class="card-footer">
          <span><i class="fa-regular fa-calendar"></i> ${art.createdAt || '2026-08-21'}</span>
          <span><i class="fa-regular fa-eye"></i> ${art.viewsCount || 0} lượt xem</span>
        </div>
      </div>
    </article>
  `).join('');
}

async function likeArticle(id) {
  try {
    const res = await API.likeArticle(id);
    if (res.success) {
      const el = document.getElementById(`like_count_${id}`);
      if (el) el.innerText = res.likesCount;
    }
  } catch (err) {
    console.error(err);
  }
}

async function loadUpcomingEvents() {
  const widgetList = document.getElementById('events_widget_list');
  if (!widgetList) return;

  try {
    const res = await API.getEvents();
    if (res.success && res.data.length > 0) {
      widgetList.innerHTML = res.data.map(ev => `
        <li class="widget-item" style="padding: 10px 0; border-bottom: 1px solid #E2E8F0;">
          <strong style="color: var(--primary-color); font-size: 13.5px; display: block;">${ev.title}</strong>
          <span style="font-size: 11.5px; color: var(--text-muted); display: block; margin-top: 2px;">
            <i class="fa-regular fa-clock"></i> ${ev.startTime} | <i class="fa-solid fa-location-dot"></i> ${ev.location}
          </span>
        </li>
      `).join('');
    } else {
      widgetList.innerHTML = `<li style="font-size: 12px; color: var(--text-muted); padding: 10px 0;">Chưa có sự kiện mới.</li>`;
    }
  } catch (err) {
    console.error(err);
  }
}

function setupFilterTabs() {
  const container = document.getElementById('category_tabs');
  if (!container) return;

  container.addEventListener('click', (e) => {
    if (e.target.classList.contains('tab-item')) {
      document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
      e.target.classList.add('active');

      const category = e.target.dataset.category || 'all';
      loadArticles(category);
    }
  });
}

function setupSearch() {
  const input = document.getElementById('search_input');
  if (!input) return;

  let timeout = null;
  input.addEventListener('input', (e) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      loadArticles('all', e.target.value.trim());
    }, 300);
  });
}

async function openArticleModal(id) {
  try {
    const res = await API.getArticleById(id);
    if (res.success) {
      const art = res.data;
      document.getElementById('modal_badge').innerText = art.categoryName;
      document.getElementById('modal_title').innerText = art.title;
      document.getElementById('modal_ai_summary').innerText = art.summary || art.title;
      document.getElementById('modal_img').src = art.image || 'images/banner.jpg';
      document.getElementById('modal_body').innerHTML = art.content || art.title;
      document.getElementById('modal_meta').innerText = `${art.author} · ${art.createdAt} · ${art.viewsCount} lượt xem`;

      renderComments(art.comments || []);
      window.currentModalArticleId = art.id;

      document.getElementById('article_modal').classList.add('active');
    }
  } catch (err) {
    console.error(err);
  }
}

function renderComments(comments) {
  const list = document.getElementById('modal_comment_list');
  if (!list) return;

  if (comments.length === 0) {
    list.innerHTML = `<p style="font-size: 13px; color: var(--text-muted); font-style: italic;">Chưa có bình luận nào. Hãy là người đầu tiên trao đổi!</p>`;
    return;
  }

  list.innerHTML = comments.map(c => `
    <div style="background: white; padding: 10px 12px; border-radius: 6px; border: 1px solid #E2E8F0; margin-bottom: 8px;">
      <div style="display: flex; justify-content: space-between; font-size: 12px;">
        <strong style="color: var(--primary-color);">${c.authorName}</strong>
        <span style="color: var(--text-muted);">${c.createdAt}</span>
      </div>
      <p style="font-size: 13px; margin-top: 4px; color: var(--text-main);">${c.commentText}</p>
    </div>
  `).join('');
}

async function addComment() {
  if (!window.currentModalArticleId) return;

  const authorName = document.getElementById('comment_author_input').value.trim();
  const commentText = document.getElementById('comment_text_input').value.trim();

  if (!commentText) {
    alert("Vui lòng nhập nội dung bình luận!");
    return;
  }

  try {
    const res = await API.addComment(window.currentModalArticleId, authorName, commentText);
    if (res.success) {
      document.getElementById('comment_text_input').value = "";
      openArticleModal(window.currentModalArticleId);
    }
  } catch (err) {
    console.error(err);
  }
}

function setupModal() {
  const backdrop = document.getElementById('article_modal');
  const closeBtn = document.getElementById('modal_close_btn');

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      backdrop.classList.remove('active');
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) backdrop.classList.remove('active');
    });
  }
}


function filterByCategory(categoryName, el = null) {
  if (el) {
    document.querySelectorAll('.nav-item-link').forEach(a => a.classList.remove('active'));
    el.classList.add('active');
  }
  loadArticles(categoryName);
  const targetSection = document.getElementById('article_container');
  if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth' });
}
