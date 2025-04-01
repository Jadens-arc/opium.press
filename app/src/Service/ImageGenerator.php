<?php
namespace App\Service;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use mysql_xdevapi\Warning;

class ImageGenerator
{
    private $doctrine;
    private $path;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->path = realpath('.') . '/shares/';
    }

    public function capsuleStory(Post $post):string
    {
        $im = imagecreate(1080, 1920);
        $bg = imagecolorallocate($im, 31, 32, 47);
        $fg = imagecolorallocate($im, 255, 255, 255);
        $linkColor = imagecolorallocate($im, 61, 62, 77);
        $titlefont = realpath('.') . '/Ubuntu/Ubuntu-Bold.ttf';
        $contentfont = realpath('.') . '/Ubuntu/Ubuntu-Regular.ttf';

        $line = 450;
        $fontsize = 80;

        // story title with wrapping
        $title = explode(" ", $post->getTitle());
        $wrappedTitle = [""];
        $titleLine = 0;

        foreach ($title as $w) {
            if (strlen($wrappedTitle[$titleLine]) + strlen($w) > 10) {
                $wrappedTitle[] = $w . " ";
                $titleLine++;
                $line += $fontsize + 10;
            } else {
                $wrappedTitle[$titleLine] .= $w . " ";
            }
        }
        $wrappedTitle = implode("\n", $wrappedTitle) ;
        imagettftext($im, $fontsize, 0, 50, 200, $fg,  $titlefont, $wrappedTitle);



        // story content with wrapping
        $content = explode(" ", strip_tags($post->getContent()));
        $wrappedContent = [""];
        $contentLine = 0;
        $fontsize = 60;

        foreach ($content as $w) {
            if (strlen($wrappedContent[$contentLine]) + strlen($w) > 25) {
                if  ($contentLine * $fontsize > 300) break;
                $wrappedContent[] = $w . " ";
                $contentLine++;
            } else {
                $wrappedContent[$contentLine] .= $w . " ";
            }
        }
        $wrappedContent = implode("\n", $wrappedContent) ;
        $wrappedContent .= "\n...";
        imagettftext($im, $fontsize, 0, 50, $line, $fg,  $contentfont, $wrappedContent);



        // story link
        imagefilledrectangle($im, 294, 1627, 785, 1716, $linkColor);
        imagettftext($im, 50, 0, 300, 1691, $bg,  $titlefont, "Link Goes Here");


        $filename = $post->getCreator()->getUsername() . "-" . $post->getUuid() . ".png";
        $fullpath = $this->path . $filename;
        imagepng($im, $fullpath);
        imagedestroy($im);
        return '/shares/' . $filename;
    }

    public function writerStory(User $user):string
    {
        $im = imagecreate(1080, 1920);
        $bg = imagecolorallocate($im, 31, 32, 47);
        $fg = imagecolorallocate($im, 255, 255, 255);
        $linkColor = imagecolorallocate($im, 61, 62, 77);
        $titlefont = realpath('.') . '/Ubuntu/Ubuntu-Bold.ttf';
        $contentfont = realpath('.') . '/Ubuntu/Ubuntu-Regular.ttf';

        $line = 450;
        $fontsize = 80;

        // story title with wrapping
        $title = explode(" ", $user->getDisplayName() . " on Opium");
        $wrappedTitle = [""];
        $titleLine = 0;

        foreach ($title as $w) {
            if (strlen($wrappedTitle[$titleLine]) + strlen($w) > 10) {
                $wrappedTitle[] = $w . " ";
                $titleLine++;
                $line += $fontsize + 10;
            } else {
                $wrappedTitle[$titleLine] .= $w . " ";
            }
        }
        $wrappedTitle = implode("\n", $wrappedTitle) ;
        imagettftext($im, $fontsize, 0, 50, 200, $fg,  $titlefont, $wrappedTitle);



        // story content with wrapping
        $content = explode(" ", '@' . $user->getUsername() . "\n\n\n" . $user->getBio());
        $wrappedContent = [""];
        $contentLine = 0;
        $fontsize = 60;

        foreach ($content as $w) {
            if (strlen($wrappedContent[$contentLine]) + strlen($w) > 25) {
                if  ($contentLine * $fontsize > 300) break;
                $wrappedContent[] = $w . " ";
                $contentLine++;
            } else {
                $wrappedContent[$contentLine] .= $w . " ";
            }
        }
        $wrappedContent = implode("\n", $wrappedContent) ;
        imagettftext($im, $fontsize, 0, 50, $line, $fg,  $contentfont, $wrappedContent);



        // story link
        imagefilledrectangle($im, 294, 1627, 785, 1716, $linkColor);
        imagettftext($im, 50, 0, 300, 1691, $bg,  $titlefont, "Link Goes Here");


        $filename = $user->getUsername() . ".png";
        $fullpath = $this->path . $filename;
        imagepng($im, $fullpath);
        imagedestroy($im);
        return '/shares/' . $filename;
    }
}
