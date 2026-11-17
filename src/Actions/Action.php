<?php

namespace Thuraaung\TableKit\Actions;

use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Thuraaung\TableKit\Support\Identifier;
use Thuraaung\TableKit\Support\SafeUrl;

class Action
{
    public string $key;
    public string $heading;
    public ?string $iconName = null;
    public int $position = 0;
    protected string $httpMethod = 'GET';
    protected ?string $confirmation = null;
    protected ?string $routeName = null;
    protected $routeParameters = [];
    protected ?string $routeKey = null;
    protected $target;
    protected $visibility;
    protected $authorization;
    protected ?string $ability = null;

    protected function __construct(string $key)
    {
        $this->key = Identifier::key($key);
        $this->heading = ucwords(str_replace('_', ' ', $key));
    }

    public static function make(string $key): self
    {
        return new static($key);
    }

    public function label(string $label): self
    {
        $this->heading = $label;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->iconName = $icon;

        return $this;
    }

    public function order(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /** Row actions default to the model route key; a string names its parameter. */
    public function route(string $name, $parameters = [], ?string $key = null): self
    {
        if (!is_array($parameters) && !is_string($parameters) && !is_callable($parameters)) {
            throw new InvalidArgumentException('Invalid route parameters.');
        }
        $this->routeName = $name;
        $this->routeParameters = $parameters;
        $this->routeKey = $key;

        return $this;
    }

    public function url($url): self
    {
        if (!is_string($url) && !is_callable($url)) {
            throw new InvalidArgumentException('Action URL must be a string or callback.');
        }
        $this->target = $url;

        return $this;
    }

    public function method(string $method): self
    {
        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Unsupported action method.');
        }
        $this->httpMethod = $method;

        return $this;
    }

    public function confirm(string $message = 'Continue with this action?'): self
    {
        $this->confirmation = $message;

        return $this;
    }

    public function visible(callable $callback): self
    {
        $this->visibility = $callback;

        return $this;
    }

    public function authorized(callable $callback): self
    {
        $this->authorization = $callback;

        return $this;
    }

    public function can(string $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function allowed($row = null): bool
    {
        return (!$this->visibility || call_user_func($this->visibility, $row, request()))
            && (!$this->authorization || call_user_func($this->authorization, $row, request()))
            && (!$this->ability || Gate::allows($this->ability, $row === null ? [] : $row));
    }

    public function resolveUrl($row = null): ?string
    {
        if ($this->routeName !== null) {
            $parameters = $this->routeParameters;
            if (is_callable($parameters)) {
                $parameters = call_user_func($parameters, $row);
            } elseif ($row !== null && $this instanceof RowAction) {
                $key = $this->routeKey ? $row->getAttribute($this->routeKey) : $row->getRouteKey();
                $parameters = is_string($parameters) ? [$parameters => $key] : ($parameters ?: [$key]);
            }

            return SafeUrl::validate(route($this->routeName, $parameters));
        }
        if ($this->target === null) {
            return null;
        }

        return SafeUrl::validate((string) (is_callable($this->target)
            ? call_user_func($this->target, $row) : $this->target));
    }

    public function render($row = null): string
    {
        if (!$this->allowed($row)) {
            return '';
        }
        $url = $this->resolveUrl($row);
        $label = e($this->heading);
        $icons = ['plus' => '+', 'download' => '↓', 'view' => '↗', 'edit' => '✎'];
        if (isset($icons[$this->iconName])) {
            $label = '<span aria-hidden="true">'.$icons[$this->iconName].'</span> '.$label;
        }
        if ($url === null) {
            return '<button type="button" disabled>'.$label.'</button>';
        }
        $confirm = $this->confirmation === null ? '' : ' data-tk-confirm="'.e($this->confirmation).'"';
        if ($this->httpMethod === 'GET') {
            if (in_array($this->key, ['delete', 'destroy'], true)) {
                throw new InvalidArgumentException('Deletion actions require a mutation method.');
            }

            return '<a class="tk-action" href="'.e($url).'"'.$confirm.'>'.$label.'</a>';
        }
        $bulk = $this instanceof BulkAction ? ' data-tk-bulk' : '';
        $method = $this->httpMethod === 'POST' ? ''
            : '<input type="hidden" name="_method" value="'.$this->httpMethod.'">';

        return '<form action="'.e($url).'" method="POST"'.$bulk.$confirm.'>'
            .'<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
            .$method.'<button type="submit">'.$label.'</button></form>';
    }
}
