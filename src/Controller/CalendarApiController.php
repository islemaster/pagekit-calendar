<?php
	namespace MHDev\Calendar\Controller;

	use Pagekit\Application as App;
	use Pagekit\User\Model\Role;
	use MHDev\Calendar\Model\Category;
	use MHDev\Calendar\Model\Event;

	class CalendarApiController
	{
		/**
		 * @Route("/categories/load", name="categories/load")
		 */
		public function loadCategoriesAction()
		{
			$categories = Category::query()->related(['author'])->get();
			
			return [
				'categories' => $categories,
				'count' => count($categories)
			];
		}
		
		/**
		 * @Route("/categories/has-events", name="categories/has-events")
		 * @Request({"categories": "array"})
		 */
		public function hasEventsAction($categories = [])
		{
			foreach ($categories as &$category) {
				$events = Event::query()->where(['category_id' => $category])->get();
				$hasEvents = count($events) > 0;
				if ($hasEvents) {
					break;
				}
			}
			
			return [
				'hasEvents' => $hasEvents
			];
		}
		
		/**
		 * @Access("category: manage categories")
		 * @Route("/categories/save", name="categories/save")
		 * @Request({"category": "array", "id": "int"}, csrf=true)
		 */
		public function saveCategoryAction($data, $id = 0)
		{
			if (!$id || !$category = Category::find($id)) {
				if ($id) {
					App::abort(404, __('Category not found.'));
				}
				$category = Category::create();
        	}
			
			$category = Category::create();
			$category->save($data);
			return ['message' => 'success', 'category' => $category];
		}
		
		/**
		* @Access("category: manage categories")
		 * @Route("/categories/remove", name="categories/remove")
		 * @Request({"ids": "array"}, csrf=true)
		 */
		public function removeCategoriesAction($ids = [])
		{
			foreach ($ids as &$id) {
				if ($id && $category = Category::find($id)) {
					$category->delete();
				} else {
					if ($id) {
						App::abort(404, __('Category not found.'));
					}
				}
			}
			return ['message' => 'success', 'category' => $category];
		}
		
		/**
		 * @Route("/events/load", name="events/load")
		 * @Request({"category": "int", "start": "string", "end": "string", "readonly" : "boolean"})
		 */
		public function loadEventsAction($category = 0, $start = null, $end = null, $readonly = false)
		{
			if (!$category) {
				if ($start == null || $end == null) {
					$events = Event::query()->related(['author'])->get();
				} else {
					$events = Event::where('start >= ? and end <= ?', [new \DateTime($start), new \DateTime($end)])->related(['author'])->get();
				}
			} else {
				if ($start == null || $end == null) {
					$events = Event::query()->where(['category_id' => $category])->related(['author'])->get();
				} else {
					$events = Event::where('category_id = ? and start >= ? and end <= ?', [$category, new \DateTime($start), new \DateTime($end)])->related(['author'])->get();
				}
			}
			
			if ($readonly) {
				foreach ($events as &$event) {
					$event->description = App::content()->applyPlugins($event->description, ['event' => $event, 'markdown' => true]);
				}
				
				$events = array_values($events);
			}
			
			return [
				'events' => $events,
				'count' => count($events),
				'start' => new \DateTime($start),
				'end' => new \DateTime($end)
			];
		}
		
		/**
		 * @Access("calendar: manage events")
		 * @Route("/events/save", name="events/save")
		 * @Request({"event": "array", "id": "int"}, csrf=true)
		 */
		public function saveEventAction($data, $id = 0)
		{
			if (!$id || !$event = Event::find($id)) {
				if ($id) {
					App::abort(404, __('Event not found.'));
				}
				$event = Event::create();
        	}
			
			$event = Event::create();
			$event->save($data);
			return ['message' => 'success', 'event' => $event];
		}
		
		/**
		 * @Access("calendar: manage events")
		 * @Route("/events/remove", name="events/remove")
		 * @Request({"ids": "array"}, csrf=true)
		 */
		public function removeEventsAction($ids = [])
		{
			foreach ($ids as &$id) {
				if ($id && $event = Event::find($id)) {
					$event->delete();
				} else {
					if ($id) {
						App::abort(404, __('Event not found.'));
					}
				}
			}
			return ['message' => 'success'];
		}
		
		/**
		 * @Access("calendar: manage events")
		 * @Route("/events/copy", name="events/copy")
		 * @Request({"ids": "array"}, csrf=true)
		 */
		public function copyEventsAction($ids = [])
		{
			foreach ($ids as &$id) {
				if ($id && $event = Event::find($id)) {
					$clonedEvent = clone $event;
					$clonedEvent->id = null;
					$clonedEvent->save();
				} else {
					if ($id) {
						App::abort(404, __('Event not found.'));
					}
				}
			}
			return ['message' => 'success'];
		}
	}