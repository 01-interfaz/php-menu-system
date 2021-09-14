<?php

namespace Interfaz\MenuSystem;

use Closure;

class Menu
{
    public static ?Closure $getCurrentURLHandle = null;

    /** @var Menu[] */
    private array $_childrens = [];
    private string $_name;
    private ?string $_url = null;
    private ?string $_icon = null;
    private $_permissionCallback = null;
    private ?Menu $_parent = null;
    private string $_label = "";

    public static function create(string $name): Menu
    {
        return new Menu($name, null);
    }

    public static function createRoot()
    {
        return static::create('_root_', null);
    }

    public function __construct(string $name, ?string $url = null)
    {
        $this->_name = $name;
        $this->_label = $name;
        $this->setUrl($url);
        $this->initilize();
    }

    protected function initilize()
    {
    }

    /**
     * Busca y Crea un menu en base a la ruta.
     * @param string|null $path
     * @return Menu
     */
    public function navTo(?string $path): Menu
    {
        $select = $this;
        if ($path != null) {
            $parts = explode("/", $path);
            foreach ($parts as $part) {
                $child = $select->getChildrenOfName($part);
                if ($child === null) {
                    $child = new Menu($path, null);
                    $select->add($child);
                }
                $select = $child;
            }
        }
        return $select;
    }

    public function add(Menu $menu, ?string $path = null): Menu
    {
        $select = $this->navTo($path);
        $select->_childrens[] = $menu;
        $menu->_parent = $select;
        return $menu;
    }

    public function setIcon(string $icon): Menu
    {
        $this->_icon = $icon;
        return $this;
    }

    public function hasIcon(): bool
    {
        return $this->_icon != null;
    }

    public function getIcon(): string
    {
        return $this->_icon;
    }

    public function setLabel(string $label) : Menu
    {
        $this->_label = $label;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->_label;
    }

    public function hasChildrens(): bool
    {
        return $this->getLength() > 0;
    }

    /**
     * Evalua si tiene hijos directos con permisos.
     *
     * @return boolean
     */
    public function hasValidChildrens(): bool
    {
        if(!$this->hasPermission()) return false;
        
        foreach($this->_childrens as $child)
        {
            if($child->hasValidChildrens($child))return true;
        }
        return false;
    }

    public function getLength(): int
    {
        return count($this->_childrens);
    }

    public function hasParent(): bool
    {
        return $this->_parent !== null;
    }

    public function getParent(): Menu
    {
        return $this->_parent;
    }

    public function getDepth(): int
    {
        $parent = $this->_parent;
        $value = 0;
        while ($parent !== null) {
            $value++;
            $parent = $parent->_parent;
        }
        return $value;
    }

    public function getChildren(int $index): Menu
    {
        return $this->_childrens[$index];
    }

    public function getChildrenOfName(string $name): ?Menu
    {
        foreach ($this->_childrens as $children)
            if (strtolower($children->getName()) === strtolower($name))
                return $children;
        return null;
    }

    public function canRender() : bool
    {
        if (!$this->hasPermission()) return false;
        foreach($this->_childrens as $child) if(!$child->canRender($child)) return false;
        return true;
    }

    public function hasPermission(): bool
    {
        if ($this->_permissionCallback == null) return true;
        return call_user_func($this->_permissionCallback);
    }

    public function setPermission(callable $callback): Menu
    {
        $this->_permissionCallback = $callback;
        return $this;
    }

    public function isActive(): bool
    {
        foreach ($this->_childrens as $children) if ($children->isActive()) return true;
        if (!$this->hasUrl()) return false;
        //return $this->getUrl() === explode('?', request()->getUri())[0];
        if (self::$getCurrentURLHandle === null) return false;
        $url = self::$getCurrentURLHandle;
        $url = $url();
        return $this->getUrl() === explode('?', $url)[0];
    }

    public function setUrl(?string $url): Menu
    {
        $this->_url = $url;
        return $this;
    }

    public function hasUrl(): bool
    {
        return $this->_url != null;
    }

    public function getUrl(): string
    {
        return $this->_url;
    }

    public function getName(): string
    {
        return $this->_name;
    }
}
