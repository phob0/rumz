<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRumPostRequest;
use App\Http\Requests\UpdateRumPostRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Favourite;
use App\Models\Image;
use App\Models\Rum;
use App\Models\RumPost;
use App\Notifications\CommentReport;
use App\Notifications\PostReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Resources\RumPostResource;

class RumPostController extends Controller
{

    public function index(Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // TODO: authorize view posts
        return JsonResource::collection($rum->posts);
    }

    public function store(StoreRumPostRequest $request): JsonResource
    {
        $paths = [];

        if (!empty($request->images)) {
            foreach ($request->images as $image) {
                array_push($paths, $image->store('public/images/posts'));
            }
        }

        $data = $request->validated();

        if (!empty($paths)) {
            foreach ($paths as $index => $path) {
                $data['images'][$index] = link_image_path($path);
            }
        }

        $rumPost = RumPost::create(
            Arr::add(Arr::except($data, ['images']), 'user_id', auth()->user()->id)
        );

        if ($rumPost->images->isEmpty() && !empty($paths)) {
            foreach ($data['images'] as $image) {
                Image::create([
                    'url' => $image,
                    'imageable_id' => $rumPost->id,
                    'imageable_type' => RumPost::class,
                ]);
            }
        }

        // TODO: notificate all rum members and create privileged users table
        return JsonResource::make($rumPost->load([
            'usersLike',
            'comments',
            'images'
        ]));
    }

    public function edit(RumPost $rumPost): RumPostResource
    {
        $this->authorize('edit', $rumPost);

        return RumPostResource::make($rumPost);
    }

    public function update(UpdateRumPostRequest $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('update', $rumPost);

        $data = $request->validated();

        $rumPost->update(
            Arr::except(
                Arr::add($data, 'user_id', auth()->user()->id),
                ['images'])
        );

        if (empty($data['images'])) {
            $rumPost->images->each(fn($item) => $this->removeImage($item->url));

            $rumPost->images()->delete();
        } else if ((!empty($rumPost->images) && !empty($data['images']))) {
            $existed = compare_images_exist($rumPost->images, $data['images']);

            $rumPost->images
                ->filter(fn($item) => !in_array(get_image_name($item->url), $existed))
                ->each(fn($item) => $this->removeImage($item->url))
                ->each(fn($item) => Image::find($item->id)->delete());

            foreach ($data['images'] as $image) {
                if (Storage::disk('local')->exists('public/images/temp/'.$image)) {
                    Storage::disk('local')->move('public/images/temp/'.$image, 'public/images/posts/'.$image);
                }

                Image::create([
                    'url' => 'storage/images/posts/' . $image,
                    'imageable_id' => $rumPost->id,
                    'imageable_type' => RumPost::class,
                ]);
            }
        }

        return response()->noContent();
    }

    public function delete(RumPost $rumPost)
    {
        $this->authorize('delete', $rumPost);

        $rumPost->delete();
    }

    public function reportPost(Request $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('reportPost', $rumPost);

        $rumPost->rum->master->notify(
            new PostReport(
                $rumPost,
            'A post has been reported. Please verify and submit a response.'
            )
        );

        return response()->noContent();
    }

    public function likeOrDislike(Request $request, $action, $type, $id): \Illuminate\Http\JsonResponse
    {
        $action = Str::of($action)->plural()->value;

        $oppositeAction = $action === 'likes' ? 'dislikes' : 'likes';

        $model = $type === 'post' ?
            RumPost::find($id) :
                (
                    $type === 'comment' ?
                        Comment::find($id) :
                        CommentReply::find($id)
                );

        $this->authorize('likeOrDislike', [RumPost::class ,$model, $type]);

        if ($model->{$action}()->where('user_id', auth()->user()->id)->count()) {
            $model->{$action}()->where('user_id', auth()->user()->id)->first()->delete();
        } else {

            if ($model->{$oppositeAction}->where('user_id', auth()->user()->id)->count()) {
                $model->{$oppositeAction}->where('user_id', auth()->user()->id)->first()->delete();
            }

            $model->{$action}()->create([
                'user_id' => auth()->user()->id
            ]);
        }

        return response()->json([
            $action.'Count' => $model->refresh()->{$action}()->count()
        ]);
    }

    public function comment(Request $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('comment', $rumPost);

        $rumPost->comments()->create([
            'user_id' => auth()->user()->id,
            'comment' => $request->comment
        ]);

        return response()->noContent();
    }

    public function allComments(Request $request, RumPost $rumPost): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('comment', $rumPost);

        return CommentResource::collection(
            $rumPost->comments()->orderBy('created_at', 'DESC')->get()
        );
    }

    public function updateComment(Request $request, RumPost $rumPost, Comment $comment): \Illuminate\Http\Response
    {
        $this->authorize('comment', $rumPost);

        $this->authorize('updateOrDeleteComment', [$rumPost, $comment]);

        $comment->update([
            'comment' => $request->comment
        ]);

        return response()->noContent();
    }

    public function deleteComment(Request $request, RumPost $rumPost, Comment $comment): \Illuminate\Http\Response
    {
        $this->authorize('updateOrDeleteComment', [$rumPost, $comment]);

        $comment->delete();

        return response()->noContent();
    }

    // TODO: Write test to check response
    public function reportComment(Request $request, RumPost $rumPost, Comment $comment): \Illuminate\Http\Response
    {
        $this->authorize('reportComment', [$rumPost, $comment]);

        $rumPost->rum->master()->notify(
            new CommentReport(
                $rumPost,
                $comment,
                [],
            'A comment has been reported. Please verify and submit a response.'
        ));

        return response()->noContent();
    }

    public function replyComment(StoreCommentRequest $request, RumPost $rumPost, Comment $comment): \Illuminate\Http\Response
    {
        $this->authorize('comment', $rumPost);

        $data = array_merge($request->validated(), [
            'id' => (string) Str::uuid(),
            'user_id' => auth()->user()->id,
        ]);

        $comment->replies()->create($data);

        return response()->noContent();
    }

    public function updateReply(StoreCommentRequest $request, RumPost $rumPost, Comment $comment, CommentReply $commentReply): \Illuminate\Http\Response
    {
        $this->authorize('updateOrDeleteReply', [$rumPost, $comment, $commentReply]);

        $commentReply->update([
            'comment' => $request->validated()['comment']
        ]);

        return response()->noContent();
    }

    public function deleteReply(Request $request, RumPost $rumPost, Comment $comment, CommentReply $commentReply): \Illuminate\Http\Response
    {
        $this->authorize('updateOrDeleteReply', [$rumPost, $comment, $commentReply]);

        $commentReply->likes()->delete();

        $commentReply->dislikes()->delete();

        $commentReply->delete();

        return response()->noContent();
    }
    // TODO: Write test to check response
    public function reportReply(Request $request, RumPost $rumPost, Comment $comment, CommentReply $commentReply): \Illuminate\Http\Response
    {
        $this->authorize('reportReply', [$rumPost, $comment, $commentReply]);

        $rumPost->rum->master()->notify(
            new CommentReport(
                $rumPost,
                $comment,
                $comment->replies->where('id', $commentReply->id)->first()),
                'A comment has been reported. Please verify and submit a response.'
        );

        return response()->noContent();
    }

    public function getFavourites(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return JsonResource::collection(auth()->user()->favourites);
    }

    public function saveFavourite(Request $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('saveFavourite', $rumPost);

        Favourite::create([
            'user_id' => auth()->user()->id,
            'post_id' => $rumPost->id
        ]);

        return response()->noContent();
    }

    public function removeFavourite(Request $request,RumPost $rumPost , Favourite $favourite): \Illuminate\Http\Response
    {
        $this->authorize('removeFavourite', [$rumPost, $favourite]);

        $favourite->delete();

        return response()->noContent();
    }

    public function lookupMetadata(Request $request): \Illuminate\Http\JsonResponse
    {
        $crawler = \Goutte::request('GET', $request->link);

        $metadata = [];
        foreach($this->metadataColumns() as $value) {
            $metadata[$value] = $crawler->filterXpath("//meta[@property='og:".$value."']")->extract(['content']);
        }

        return response()->json($metadata);
    }

    private function metadataColumns(): array
    {
        return [
            'site_name',
            'title',
            'description',
            'image',
            'video',
        ];
    }
}
