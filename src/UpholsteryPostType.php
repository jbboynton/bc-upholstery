<?php

namespace BC\Upholstery;

use PostTypes\PostType;

<<<<<<< HEAD:src/UpholsteryPostType.php
class UpholsteryPostType {
  public const ID = 'upholstery_service';
  public const PLURAL_NAME = 'Upholstery';
  public const SINGULAR_NAME = 'Upholstery Service';
  public const SLUG = 'upholstery';
=======
class CanvasPostType {
  public const ID = 'canvas_service';
  public const PLURAL_NAME = 'Canvas Services';
  public const SINGULAR_NAME = 'Canvas Service';
  public const SLUG = 'canvas';
>>>>>>> parent of 4600e1e... Fix typos and bad labelling:src/CanvasPostType.php

  private $cpt;
  private $icon = 'dashicons-admin-page';

  public function __construct() {
    $this->create_post_type();

    $this->set_options();
    $this->set_labels();

    $this->cpt->icon($this->icon);
    $this->cpt->register();
  }

  private function create_post_type() {
    $this->cpt = new PostType($this->names());
  }

  private function names() {
    return [
      'name' => self::ID,
      'singular' => self::SINGULAR_NAME,
      'plural' => self::PLURAL_NAME,
      'slug' => self::SLUG,
    ];
  }

  private function set_options() {
    $this->cpt->options([
      'public' => true,
      'show_in_nav_menus' => false,
      'show_in_menu_bar' => false,
      'menu_position' => 21.5,
      'supports' => ['revisions', 'page-attributes'],
      'has_archive' => self::SLUG,
      'rewrite' => ['slug' => self::SLUG, 'with_front' => false],
    ]);
  }

  private function set_labels() {
    $this->cpt->labels([
      'search_items' => self::PLURAL_NAME,
      'archives' => self::PLURAL_NAME,
      'menu_name' => self::PLURAL_NAME,
<<<<<<< HEAD:src/UpholsteryPostType.php
      'not_found' => 'No upholstery services found',
      'not_found_in_trash' => 'No upholstery services found in Trash',
      'search_items' => 'Search Upholstery Services',
      'search_items' => self::PLURAL_NAME,
      'view_items' => 'View Upholstery Services',
=======
>>>>>>> parent of 4600e1e... Fix typos and bad labelling:src/CanvasPostType.php
    ]);
  }
}
