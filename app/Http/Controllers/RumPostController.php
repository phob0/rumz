<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRumPostRequest;
use App\Http\Requests\UpdateRumPostRequest;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Favourite;
use App\Models\Rum;
use App\Models\RumPost;
use App\Notifications\CommentReport;
use App\Notifications\PostReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RumPostController extends Controller
{
    /* TODO:remove title field, image, upload image
     * remove title field
     */
    public function index(Rum $rum): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // TODO: authorize view posts
        return JsonResource::collection($rum->posts);
    }

    public function store(StoreRumPostRequest $request): JsonResource
    {
        $data = $request->validated();
//        $this->authorize('create', $data['rum_id']);
        // TODO: notificate all rum members and create privileged users table
        return JsonResource::make(
            RumPost::create(
                Arr::add($data, 'user_id', auth()->user()->id)
            )
        );
    }

    public function edit(RumPost $rumPost): JsonResource
    {
        $this->authorize('edit', $rumPost);

        return JsonResource::make($rumPost);
    }

    public function update(UpdateRumPostRequest $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('update', $rumPost);

        $data = $request->validated();

        $rumPost->update(
            Arr::add($data, 'user_id', auth()->user()->id)
        );

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

    public function comment(Request $request, RumPost $rumPost): \Illuminate\Http\JsonResponse
    {
        $this->authorize('comment', $rumPost);

        $rumPost->comments()->create([
            'user_id' => auth()->user()->id,
            'comment' => $request->comment
        ]);

        return response()->json([
            'commentsCount' => $rumPost->refresh()->comments()->count()
        ]);
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
