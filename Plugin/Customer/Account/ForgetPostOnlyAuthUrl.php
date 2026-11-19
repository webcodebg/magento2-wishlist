<?php

namespace Webcode\Wishlist\Plugin\Customer\Account;

use Magento\Customer\Model\Account\Redirect;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * Wishlist actions store their own URL as "where to return after login", even for a guest.
 * The return is a GET, so a POST-only action answers it with 404.
 */
class ForgetPostOnlyAuthUrl
{
    private const POST_ONLY_ACTION = '#/wishlist/index/(allcart|cart|fromcart|remove|send|update|updateitemoptions)(/|\?|$)#i';

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * Drop the stored URL, so the core falls back to its usual login redirect
     *
     * @param Redirect $subject
     *
     * @return void
     */
    public function beforeGetRedirect(Redirect $subject): void
    {
        if ($this->isPostOnly($this->customerSession->getBeforeAuthUrl())) {
            $this->customerSession->getBeforeAuthUrl(true);
        }

        if ($this->isPostOnly($this->customerSession->getAfterAuthUrl())) {
            $this->customerSession->getAfterAuthUrl(true);
        }
    }

    /**
     * @param string|null $url
     *
     * @return bool
     */
    private function isPostOnly(?string $url): bool
    {
        return $url !== null && preg_match(self::POST_ONLY_ACTION, $url) === 1;
    }
}
