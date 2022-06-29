<?php

namespace wsydney76\inertia\models;

use craft\base\Model;

class Settings extends Model
{

    /** The template that will be rendered on first calls.
     *
     *  Includes the div the inertia app will be rendered to:
     *  <div id="app" data-page="{{ page|json_encode }}"></div>
     *
     * and calls the inertia js app
     * <script src="<path_to_app>/app.js"></script>
     *
     */
    public string $view = 'inertia/inertia.twig';

    /** The key the adapter uses for handling shared props */
    public string $shareKey = '__inertia__';

    /** whether inertia's assets versioning shall be used
     * Set to false if this is already handled in your build process
     */
    public bool $useVersioning = true;

    /** Array of directories that will be checked for changed assets if useVersioning = true
     *  Supports environment variables and aliases.
     */
    public array $assetsDirs = ['@webroot/assets'];
}
