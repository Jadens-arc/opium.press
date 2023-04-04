<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\EditUserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;



class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setRoles(["ROLE_USER"]);
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
                
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/writer/edit', name: 'app_edit_account')]
    public function edit(Request $request,
                         EntityManagerInterface $entityManager,
                         UserInterface $user) {

        $form = $this->createForm(EditUserFormType::class);

        $form->handleRequest($request, $user);

        if ($form->isSubmitted()) {
            $user->setEmail($form['email']->getData());
            $user->setDisplayName($form['display_name']->getData());
            $user->setBio($form['bio']->getData());
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_writer', ['username' => $user->getUsername()]);
        }

        return $this->renderForm('registration/edit.html.twig', [
            'form' => $form,
            'user' => $user
        ]);
    }
}
