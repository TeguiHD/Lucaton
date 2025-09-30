<?php

class NewsController {
    private NewsArticle $articles;
    private NewsCategory $categories;

    public function __construct() {
        $this->articles = new NewsArticle();
        $this->categories = new NewsCategory();
    }

    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'search' => trim($_GET['q'] ?? ''),
            'category_slug' => trim($_GET['categoria'] ?? ''),
            'date_from' => $_GET['desde'] ?? '',
            'date_to' => $_GET['hasta'] ?? ''
        ];

        $result = $this->articles->getPublished($filters, $page, 9);
        $categories = $this->categories->allWithCounts();

        $page_title = 'Noticias Lucatón';
        $page_description = 'Descubre las últimas novedades, historias y comunicados oficiales de Lucatón.';

        $news_items = $result['data'];
        $pagination = $result['pagination'];

        include VIEWS_PATH . '/public/news/index.php';
    }

    public function show($slug) {
        $slug = trim($slug ?? '');
        if ($slug === '') {
            return $this->notFound();
        }

        $article = $this->articles->findBySlug($slug);
        if (!$article) {
            return $this->notFound();
        }

        $related = $this->articles->getRelated((int)$article['id'], $article['category_id'] ?? null, 3);
        $recent = $this->articles->getRecent(4, (int)$article['id']);

        $page_title = $article['meta_title'] ?? ($article['title'] . ' — Noticias Lucatón');
        $page_description = $article['meta_description'] ?? ($article['summary'] ?? 'Noticias de Lucatón');

        $article_url = Router::url('noticias/' . $article['slug']);
        $share_text = $article['title'];
        $share_links = [
            'whatsapp' => 'https://api.whatsapp.com/send?text=' . urlencode($share_text . ' ' . $article_url),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($article_url),
            'instagram' => 'https://www.instagram.com/?url=' . urlencode($article_url),
            'x' => 'https://twitter.com/intent/tweet?text=' . urlencode($share_text) . '&url=' . urlencode($article_url),
            'linkedin' => 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($article_url) . '&title=' . urlencode($share_text),
            'email' => 'mailto:?subject=' . rawurlencode($share_text) . '&body=' . rawurlencode($article_url)
        ];

        include VIEWS_PATH . '/public/news/show.php';
    }

    private function notFound(): void {
        http_response_code(404);
        if (file_exists(VIEWS_PATH . '/errors/404.php')) {
            include VIEWS_PATH . '/errors/404.php';
        } else {
            echo '<h1>404 - Noticia no encontrada</h1>';
        }
    }
}
