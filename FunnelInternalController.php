<?php

namespace App\Http\Controllers;

use App\Http\Requests\FunnelInternalRequest;
use App\SiteSource;
use App\User;
use App\Widget;
use App\Widgets\FunnelInternal;
use Auth;
use Illuminate\Http\Request;

class FunnelInternalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('index',FunnelInternal::class);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize(FunnelInternal::class);

        $blockCKE = true; // for disable CKEditor and leave only Summernote editor
        $siteSources = SiteSource::get()->sortBy('display');
        $enterprise = Auth::user()->getSelectedEnterprise();
        $locations = Widget::getLocations();
        $team = User::getFullTeam();

        return view('widgets.funnel-internals.create', compact('blockCKE', 'enterprise','locations','siteSources','team'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FunnelInternalRequest $request)
    {
        $this->authorize(FunnelInternal::class);

        $funnel = new FunnelInternal();
        $funnel->user_id = Auth::id();
        $funnel->team_id = Auth::user()->team_id;
        $funnel->alias = FunnelInternal::generateAlias();
        $funnel->fillAndSave($request);

        if($funnel){
            Widget::addNew(FunnelInternal::class, $funnel->id);
        }

        if(!Auth::user()->can('view',$funnel))
        {
            return redirect(route('widgets'))->with('success',__('widgets.added'));
        }

        return redirect(route('funnel-internals.show',$funnel->alias))->with('success',__('widgets.added'));;

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $funnel = $this->getFunnel($id);
        $this->authorize($funnel);

        $blockCKE = true;
        $locations = Widget::getLocations();
        $siteSources = SiteSource::get()->sortBy('display');

        return view('widgets.funnel-internals.show', compact('blockCKE','funnel','locations', 'siteSources'));
    }

    private function getFunnel($id)
    {
        if($funnel = FunnelInternal::byAlias($id))
        {
            return $funnel;
        }

        if($funnel = FunnelInternal::find($id))
        {
            return $funnel;
        }

        return NULL;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $funnel = $this->getFunnel($id);
        $this->authorize($funnel);

        $blockCKE = true;
        $locations = Widget::getLocations();
        $siteSources = SiteSource::get()->sortBy('display');
        $team = User::getFullTeam();

        return view('widgets.funnel-internals.edit', compact('blockCKE','funnel','locations','siteSources','team'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FunnelInternalRequest $request, $id)
    {
        $funnel = $this->getFunnel($id);
        $this->authorize($funnel);

        $funnel->fillAndSave($request);

        if(!Auth::user()->can('view',$funnel))
        {
            return redirect(route('widgets'))->with('success',__('widgets.added'));
        }

        return redirect(route('funnel-internals.show',$funnel->alias))->with('success',__('widgets.added'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $funnel = FunnelInternal::findOrFail($id);
        $this->authorize($funnel);

        Widget::where('widgetable_id',$id)
            ->where('widgetable_type',FunnelInternal::class)
            ->delete();

        $funnel->delete();

        return redirect(route('widgets'))->with('success',__('widgets.deleted'));

    }

    public function embedData($alias)
    {
        $funnel = FunnelInternal::byAlias($alias);

        if(!$funnel){
            echo '';
            return;
        }

        return view('embed.funnel-internals', compact('funnel'));
    }

    public function getSelectedLocations(Request $request)
    {
        $funnel = null;
        $locations = Widget::getLocations($request->id);
        if ($request->funnel != 'underfined') {
            $funnel = $this->getFunnel($request->funnel);
        }
        $returnHTML = view('widgets._locations', compact('locations', 'funnel'))->render();

        return response()->json([
            'success' => true,
            'html'    => $returnHTML,
        ]);
    }
}

