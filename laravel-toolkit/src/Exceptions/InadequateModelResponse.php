<?php

namespace Keystone\Toolkit\Exceptions;

use RuntimeException;

/**
 * Thrown when a model produces output that is unusable for the task (empty, malformed,
 * or rejected by the caller's adequacy check). These count as strikes against the model;
 * transient/infra failures use the base RuntimeException and do not.
 */
class InadequateModelResponse extends RuntimeException
{
}
