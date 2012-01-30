<?php

namespace Netvlies\PublishBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Netvlies\PublishBundle\Entity\ApplicationRepository;
use Netvlies\PublishBundle\Entity\Application;
use Netvlies\PublishBundle\Entity\ScriptBuilder;
use Netvlies\PublishBundle\Entity\Deployment;

use Netvlies\PublishBundle\Form\FormApplicationEditType;
use Netvlies\PublishBundle\Form\FormApplicationEnrichType;
use Netvlies\PublishBundle\Form\FormApplicationDeployType;
use Netvlies\PublishBundle\Form\FormExecuteType;
use Netvlies\PublishBundle\Form\ChoiceList\BranchesType;


class ApplicationController extends Controller {



    /**
     * This action is used as subaction to load all available applications into its template, which is almost always used
     *
     * @Route("/application/list")
     * @Template()
     */
    public function listAction(){
        $oEntityManager = $this->getDoctrine()->getEntityManager();
        $apps = $oEntityManager->getRepository('NetvliesPublishBundle:Application')->getAll();
        return array('apps' => $apps);
    }

    /**
     *
     * @Route("/application/{id}/targets")
     * @Template()
	 */    
    public function targetsAction($id) {

        $oEntityManager = $this->getDoctrine()->getEntityManager();

        /**
         * @var \Netvlies\PublishBundle\Entity\Application $app
         */
        $app = $oEntityManager->getRepository('NetvliesPublishBundle:Application')->findOneById($id);


        $query = $oEntityManager->createQuery('
            SELECT t FROM Netvlies\PublishBundle\Entity\Target t
            INNER JOIN t.environment e
            WHERE t.application = :app
            ORDER BY e.type, e.hostname
        ');

        $query->setParameter('app', $app);
        $targets = $query->getResult();

        $allTwigParams = array();
        $allTwigParams['application'] = $app;
        $allTwigParams['targets'] = $targets;
//        $allTwigParams['revision'] = $revision;

        // Git reference selector form

        /**
         * @var \Netvlies\PublishBundle\Services\GitBitbucket $gitService
         */
//        $gitService = $this->get('git');
//        $gitService->setApplication($app);
//        $remoteBranches = $gitService->getRemoteBranches();
//        $branchType = new BranchesType($remoteBranches);

//        $form = $this->createForm(new FormExecuteType(), $app, array('branchchoice' => $branchType));
//        $request = $this->getRequest();

//        if($request->getMethod() == 'POST'){
//            $form->bindRequest($request);
//            $allTwigParams['changesets'] = $gitService->getLastChangesets();
//            $allTwigParams['bitbucketChangesetURL'] = $gitService->getBitbucketChangesetURL();
//        }

        //$allTwigParams['form'] = $form->createView();

        return $allTwigParams;
    }

    /**
     * @Route("/application/{id}/view")
     * @Template()
	 */
    public function viewAction($id) {

        $oEntityManager = $this->getDoctrine()->getEntityManager();
        $app = $oEntityManager->getRepository('NetvliesPublishBundle:Application')->findOneById($id);

        $allTwigParams = array();
        $allTwigParams['application'] = $app;

        return $allTwigParams;
    }

    /**
     * This will load a template with an iframe where a console is loaded with params below
þ
     * @Route("/application/{id}/execute/{deployid}/{revision}")
     * @Route("/application/{id}/execute/{deployid}")
     *
     * @Template("NetvliesPublishBundle:Application:view.html.twig")
	 */
    public function executeAction($id, $deployid, $revision=null){
        $twigParams = $this->viewAction($id, $revision);
        $twigParams['deployid'] = $deployid;
        $twigParams['revision'] = $revision;
        return $twigParams;
    }


    /**
     * @Route("/application/edit/{id}")
     * @Template()
     */
    public function editAction($id){

        $em  = $this->getDoctrine()->getEntityManager();
        /**
         * @var \Netvlies\PublishBundle\Entity\Application $app
         */
        $app = $em->getRepository('NetvliesPublishBundle:Application')->findOneById($id);
        $currentReference = $app->getReferenceToFollow();

        $gitService = $this->get('git');
        $gitService->setApplication($app);


        $remoteBranches = $this->getRequest()->getSession()->get('remoteBranches');

        if(is_null($remoteBranches)){
            //@todo we need to rethink this, because the session is also reused when not saving the item .... which is bad...
            // Conditionally set remote branches if session is not there yet
            $remoteBranches = $gitService->getRemoteBranches();
            $this->getRequest()->getSession()->set('remoteBranches', $remoteBranches);
        }

        $form = $this->createForm(new FormApplicationEditType(), $app, array('branchchoice' => new BranchesType($remoteBranches)));
        $request = $this->getRequest();

        if($request->getMethod() == 'POST'){

            $form->bindRequest($request);

            if($form->isValid()){

                $newReference = $app->getReferenceToFollow();
                $app->setBranchToFollow($remoteBranches[$newReference]);
                $this->getRequest()->getSession()->remove('remoteBranches');

                $em->persist($app);
                $em->flush();

                if($currentReference == $newReference){
                    return $this->redirect($this->generateUrl('netvlies_publish_application_view', array('id'=>$id)));
                }
                else{
                    return $this->redirect($this->generateUrl('netvlies_publish_git_checkout', array('id'=>$id, 'reference'=>$newReference)));
                }
            }
        }

        return array(
            'form' => $form->createView(),
            'application' => $app,
        );
    }

    /**
     * @Route("/application/enrich/{id}")
     * @Template()
     */
    public function enrichAction($id){

        $em  = $this->getDoctrine()->getEntityManager();
        $sRepositoryPath = $this->container->getParameter('repositorypath');

        /**
         * @var \Netvlies\PublishBundle\Entity\Application $app
         */
        $app = $em->getRepository('NetvliesPublishBundle:Application')->getApp($id, $sRepositoryPath);
        $form = $this->createForm(new FormApplicationEnrichType(), $app);
        $request = $this->getRequest();


        if($request->getMethod() == 'POST'){

            $form->bindRequest($request);

            if($form->isValid()){
                $em->persist($app);
                $em->flush();

                // Create git repo, add basic files
                //@todo Do initialization
                $type = $app->getType()->getName();

                switch($type){
                    case 'OMS':

                        break;
                    case 'Symfony2':
                        // execute symfony.sh from command dir to create and import new symfony2 project

                        break;
                    case 'Basissite v1':

                        break;
                    case 'Custom':

                        break;
                }
            }
        }

        return  array(
            'form' => $form->createView(),
            'application' => $app
        );

    }


    /**
     * @Route("/application/dashboard/{id}")
     * @Template()
     */
    public function dashboardAction($id){
        //add copy content form bind entity Deployment to this form
        // FormApplicationDeploy
        $em  = $this->getDoctrine()->getEntityManager();
        /**
         * @var \Netvlies\PublishBundle\Entity\Application $app
         */
        $app = $em->getRepository('NetvliesPublishBundle:Application')->findOneById($id);

        $gitService = $this->get('git');
        $gitService->setApplication($app);


        $remoteBranches = $this->getRequest()->getSession()->get('remoteBranches');

        if(is_null($remoteBranches)){
            // Conditionally set remote branches if session is not there yet
            $remoteBranches = $gitService->getRemoteBranches();
            $this->getRequest()->getSession()->set('remoteBranches', $remoteBranches);
        }

        $form = $this->createForm(new FormApplicationDeployType(), new Deployment(), array('branchchoice' => new BranchesType($remoteBranches)));
        $request = $this->getRequest();


        if($request->getMethod() == 'POST'){

            $form->bindRequest($request);

            if($form->isValid()){

            }
        }

        return array(
            'form' => $form->createView(),
            'application' => $app,
        );
    }
}
