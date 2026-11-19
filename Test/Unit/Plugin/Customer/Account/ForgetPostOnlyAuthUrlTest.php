<?php

namespace Webcode\Wishlist\Test\Unit\Plugin\Customer\Account;

use Magento\Customer\Model\Account\Redirect;
use Magento\Customer\Model\Session as CustomerSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Webcode\Wishlist\Plugin\Customer\Account\ForgetPostOnlyAuthUrl;

class ForgetPostOnlyAuthUrlTest extends TestCase
{
    /**
     * @return array
     */
    public static function urls(): array
    {
        return [
            'remove' => ['https://example.com/wishlist/index/remove/', true],
            'remove with params' => ['https://example.com/wishlist/index/remove/item/76516/', true],
            'store code in path' => ['https://example.com/en/wishlist/index/remove/', true],
            'query string' => ['https://example.com/wishlist/index/cart?item=1', true],
            'no trailing slash' => ['https://example.com/wishlist/index/update', true],
            'fromcart' => ['https://example.com/wishlist/index/fromcart/', true],
            'allcart' => ['https://example.com/wishlist/index/allcart/', true],
            'send' => ['https://example.com/wishlist/index/send/', true],
            'updateItemOptions' => ['https://example.com/wishlist/index/updateItemOptions/', true],
            'add answers GET' => ['https://example.com/wishlist/index/add/', false],
            'wishlist page' => ['https://example.com/wishlist/index/index/', false],
            'configure' => ['https://example.com/wishlist/index/configure/id/1/', false],
            'similar action name' => ['https://example.com/wishlist/index/removed/', false],
            'other page' => ['https://example.com/checkout/cart/', false],
        ];
    }

    #[DataProvider('urls')]
    public function testForgetsOnlyPostOnlyActions(string $url, bool $forgotten): void
    {
        $data = ['getBeforeAuthUrl' => $url, 'getAfterAuthUrl' => $url];
        $this->runPlugin($data);

        $expected = $forgotten ? null : $url;
        $this->assertSame($expected, $data['getBeforeAuthUrl']);
        $this->assertSame($expected, $data['getAfterAuthUrl']);
    }

    public function testEmptySessionStaysEmpty(): void
    {
        $data = ['getBeforeAuthUrl' => null, 'getAfterAuthUrl' => null];
        $this->runPlugin($data);

        $this->assertNull($data['getBeforeAuthUrl']);
        $this->assertNull($data['getAfterAuthUrl']);
    }

    public function testChecksBothUrlsSeparately(): void
    {
        $data = [
            'getBeforeAuthUrl' => 'https://example.com/checkout/',
            'getAfterAuthUrl' => 'https://example.com/wishlist/index/remove/',
        ];
        $this->runPlugin($data);

        $this->assertSame('https://example.com/checkout/', $data['getBeforeAuthUrl']);
        $this->assertNull($data['getAfterAuthUrl']);
    }

    /**
     * Session getters are magic: get...(true) returns the value and clears it.
     *
     * @param array $data
     *
     * @return void
     */
    private function runPlugin(array &$data): void
    {
        $session = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $session->method('__call')->willReturnCallback(
            function (string $method, array $args) use (&$data) {
                $value = $data[$method];
                if (($args[0] ?? false) === true) {
                    $data[$method] = null;
                }

                return $value;
            }
        );

        (new ForgetPostOnlyAuthUrl($session))->beforeGetRedirect($this->createMock(Redirect::class));
    }
}
