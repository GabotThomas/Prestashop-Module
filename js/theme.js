

index();


export default function index() {
  const $ = global.$;
  $('.js-reset-search').on("click",(event)=>{
    
    $.post($(event.currentTarget).data('url')).then(() => {
      window.location.assign($(event.currentTarget).data('redirect'));
      console.log("test");
    }
    );
  });
  
}
