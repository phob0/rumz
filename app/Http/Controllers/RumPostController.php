<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreRumPostRequest;
use App\Http\Requests\UpdateRumPostRequest;
use App\Models\Comment;
use App\Models\Rum;
use App\Models\RumPost;
use App\Notifications\CommentReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RumPostController extends Controller
{
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
    // TODO: Write test to check response
    public function reportPost(Request $request, RumPost $rumPost): \Illuminate\Http\Response
    {
        $this->authorize('reportPost', $rumPost);

        $rumPost->rum->master()->notify(
            new PostReport(
                $rumPost,
            'A post has been reported. Please verify and submit a response.'
            )
        );

        return response()->noContent();
    }

    public function like(Request $request, RumPost $rumPost): \Illuminate\Http\JsonResponse
    {
        $this->authorize('likeOrComment', $rumPost);

        if ($rumPost->likes()->where('user_id', auth()->user()->id)->count()) {
            $rumPost->likes()->where('user_id', auth()->user()->id)->delete();
        } else {
            $rumPost->likes()->create([
                'user_id' => auth()->user()->id
            ]);
        }

        return response()->json([
            'likesCount' => $rumPost->refresh()->likes()->count()
        ]);
    }

    public function comment(Request $request, RumPost $rumPost): \Illuminate\Http\JsonResponse
    {
        $this->authorize('likeOrComment', $rumPost);

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
        $this->authorize('likeOrComment', $rumPost);

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
        $this->authorize('likeOrComment', $rumPost);


        $data = array_merge($request->validated(), [
            'id' => (string) Str::uuid(),
            'user_id' => auth()->user()->id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        if (is_null($comment->reply)) {
            $comment->update([
                'reply' => [$data]
            ]);
        } else {
            $comment->update([
                'reply' => Arr::prepend($comment->reply, $data)
            ]);
        }

        return response()->noContent();
    }

    public function updateReply(StoreCommentRequest $request, RumPost $rumPost, Comment $comment, $reply_id): \Illuminate\Http\Response
    {
        $this->authorize('updateOrDeleteReply', [$rumPost, $comment, $reply_id]);

        $newComment = $request->validated()['comment'];

        $key = collect($comment->reply)->where('id', $reply_id)->keys()->first();

        $data = $comment->reply[$key];
        $data['comment'] = $newComment;
        $data['updated_at'] = Carbon::now();

        $comment->update([
            'reply' => collect($comment->reply)->replace([$key => $data])->sortBy('updated_at')->values()->all()
        ]);

        return response()->noContent();
    }

    public function deleteReply(Request $request, RumPost $rumPost, Comment $comment, $reply_id): \Illuminate\Http\Response
    {
        $this->authorize('updateOrDeleteReply', [$rumPost, $comment, $reply_id]);

        $data = $comment->reply;

        unset($data[collect($comment->reply)->where('id', $reply_id)->keys()->first()]);

        $comment->update([
            'reply' => $data
        ]);

        return response()->noContent();
    }
    // TODO: Write test to check response
    public function reportReply(Request $request, RumPost $rumPost, Comment $comment, $reply_id): \Illuminate\Http\Response
    {
        $this->authorize('reportReply', [$rumPost, $comment, $reply_id]);

        $rumPost->rum->master()->notify(
            new CommentReport(
                $rumPost,
                $comment,
                collect($comment->reply)->where('id', $reply_id)->first()),
                'A comment has been reported. Please verify and submit a response.'
        );

        return response()->noContent();
    }

    /*
     * TODO
     *  Common users can like, dislike and share comments
     *  Common users can save posts on favorites, share posts.
     */

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
