<?php
use Symfony\Component\HttpFoundation\Request;

class Ethna_Request extends Request
{
    /**
     *
     */
    public function getHttpVars(): array
    {
        if ($this->isMethod('POST')) {
            $http_vars = $this->request->all();
        } else {
            $http_vars = $this->query->all();
        }

        return $http_vars;
    }

}
