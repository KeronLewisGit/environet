<x-error-page code="403" :message="isset($exception) && str_contains($exception->getMessage(), 'link') ? $exception->getMessage() : null" />
