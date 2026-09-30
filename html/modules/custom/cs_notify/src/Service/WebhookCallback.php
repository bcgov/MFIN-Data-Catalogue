<?php

/**
 * This is still very much "TO DO". But here is a plan.
 * 
 * When we hand off an email to the Notify API, it gives us a message ID.
 * Store this via Drupal's "KeyValue" pairs.
 *  use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
 * 
 * Later, the Notify API can call us back via a webhook, to let us know if that ID was delivered successfully or not.
 * 
 * At that point, we log the result, and remove it from the KeyValue storage.
 * 
 * Use a "KeyValueExpirable", make it expire after a few days, in case of glitch.
 * 
 */