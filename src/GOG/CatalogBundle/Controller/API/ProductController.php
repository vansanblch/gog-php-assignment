<?php namespace GOG\CatalogBundle\Controller\API;


use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use GOG\CatalogBundle\Form\ProductFormFactory;
use GOG\CatalogBundle\Service\ProductPager;
use GOG\CatalogBundle\Service\ProductManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends FOSRestController
{
    /**
     * @var ProductManager
     */
    public $productManager;

    /**
     * @var ProductPager
     */
    public $productPager;

    public function getProductsAction(Request $request)
    {
        $page = $request->query->get('page', 0);
        $pager = $this->productPager->create($page);

        $result = $this->productManager->getAllProductsQuery($pager);

        $view = $this->view([
            'result' => $result,
        ], 200);

        return $this->handleView($view);
    }

    public function postProductAction(Request $request)
    {
        $product = $this->productManager->createProduct();

        $form = $this->get('gog_catalog.form_factory.product')->createForm();
        $form->setData($product);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->productManager->saveProduct($product);

            $view = $this->view($product, 201);

            return $this->handleView($view);
        }

        return $this->handleView(
            View::create([
                'error' => true,
                'code' => 400,
                'message' => (string) $form->getErrors(true) ?: 'Invalid request',
            ], 400)
        );
    }

    public function deleteProductAction($id)
    {
        if ($product = $this->productManager->findById($id)) {
            $this->productManager->deleteProduct($product);
        }

        return $this->handleView(View::create(null, Response::HTTP_NO_CONTENT));
    }

    public function patchProductAction(Request $request, $id)
    {
        $product = $this->productManager->findById($id);

        if (null === $product) {
            return $this->handleView($this->onProductNotFound($id));
        }

        $form = $this->get('gog_catalog.form_factory.update_product')->createForm();
        $form->setData($product);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->productManager->saveProduct($product);

            return $this->handleView($this->onPatchProductSuccess($form));
        }

        return $this->handleView($this->onPatchProductError($form));
    }

    /**
     * @param $id
     * @return View
     */
    private function onProductNotFound($id)
    {
        return View::create([
            'error' => sprintf('Product %s not found', $id),
            'code' => Response::HTTP_NOT_FOUND,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @param FormInterface $form
     * @return View
     */
    private function onPatchProductError(FormInterface $form)
    {
        return View::create([
            'code' => Response::HTTP_BAD_REQUEST,
            'error' => $form->getErrors(),
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param FormInterface $form
     * @return View
     */
    private function onPatchProductSuccess(FormInterface $form)
    {
        return View::create([
            'product' => $form->getData(),
        ], Response::HTTP_OK);
    }
}