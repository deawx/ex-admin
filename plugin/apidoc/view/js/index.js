Vue.component('menu-list', {
    props:['data','parent','openIds'],
    methods:{
        menuCollapse(id){
            const index = this.openIds.indexOf(id)
            if(index > -1){
                this.openIds.splice(index,1)
            }else{
                this.openIds.push(id)
            }
            this.$emit('update:open-ids', this.openIds)
        },
    },
    template:`<div class="menu-item" v-if="!parent || openIds.indexOf(parent.id) > -1">
                    <template v-if="data.type =='group'">
                            <div class="flex items-center text-xs leading-7">
                                <div class="w-4 h-4 hover:bg-gray-200 flex items-center justify-center cursor-pointer mr-1" @click="menuCollapse(data.id)">
                                    <div :class="openIds.indexOf(data.id) > -1 ? 'triangle-down':'triangle-right'"></div>
                                </div>
                                <span class="text-yellow-500 mr-1.5">
                                    <svg v-if="openIds.indexOf(data.id) > -1" width="23" height="24" viewBox="0 2 25 15" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd" opacity=".6"><path d="M4.9 1h5.027c.24 0 .468.1.637.276l2.172 2.272c.17.177.398.276.637.276H21.1c.497 0 .9.42.9.94V16.03c0 .52-.403.97-.9.97H4.9c-.497 0-.9-.45-.9-.97V1.94c0-.518.403-.94.9-.94z" fill="none"></path><path d="M4.9 1h5.027c.24 0 .468.1.637.276l2.172 2.272c.17.177.398.276.637.276H21.1c.497 0 .9.42.9.94V16.03c0 .52-.403.97-.9.97H4.9c-.497 0-.9-.45-.9-.97V1.94c0-.518.403-.94.9-.94z" stroke="currentColor" stroke-width="2"></path><path d="M3.22 16.358l-2.18-7.2C.863 8.58 1.3 8 1.91 8h21.18c.61 0 1.046.58.87 1.158l-2.18 7.2c-.115.38-.468.642-.87.642H4.09c-.402 0-.755-.26-.87-.642z" fill="#F8F8F8"></path><path d="M3.22 16.358l-2.18-7.2C.863 8.58 1.3 8 1.91 8h21.18c.61 0 1.046.58.87 1.158l-2.18 7.2c-.115.38-.468.642-.87.642H4.09c-.402 0-.755-.26-.87-.642z" stroke="currentColor" stroke-width="2"></path></g></svg>
                                    <svg v-else width="23" height="24" viewBox="-3 2 25 15" xmlns="http://www.w3.org/2000/svg"><path d="M1.9 1h5.027c.24 0 .467.1.637.276l2.172 2.272c.17.177.398.276.637.276H18.1c.497 0 .9.42.9.94V16.03c0 .52-.403.97-.9.97H1.9c-.497 0-.9-.45-.9-.97V1.94c0-.518.403-.94.9-.94z" stroke-width="2" stroke="currentColor" fill="none" opacity=".6"></path></svg>
                                </span>
                                <span class="font-bold cursor-pointer hover:text-gray-600"><a :href="'#doc'+data.id">{{data.name}}</a></span>
                            </div>
                            <template v-for="item in data.children">
                                <menu-list :data="item" :parent="data" :open-ids.sync="openIds"></menu-list>
                            </template>
                    </template>
                    <div v-else class="h-7 flex items-center">
                            <div v-if="data.method == 'GET'" class="text-green-500 mr-1.5 text-xs font-bold w-12 text-right">{{data.method}}</div>
                            <div v-if="data.method == 'POST'" class="text-yellow-500 mr-1.5 text-xs font-bold w-12 text-right">{{data.method}}</div>
                            <div v-if="data.method == 'PUT'" class="text-blue-500 mr-1.5 text-xs font-bold w-12 text-right">{{data.method}}</div>
                            <div v-if="data.method == 'DELETE'" class="text-red-500 mr-1.5 text-xs font-bold w-12 text-right">{{data.method}}</div>
                            <span class="text-xs cursor-pointer hover:text-gray-700"><a :href="'#doc'+data.id">{{data.name}}</a></span>
                    </div>
            </div>`
})
Vue.component('doc-item', {
    props:['data'],
    data(){
        return {
            response:false,
            collapse:true,
            loading:false,
        }
    },

    methods:{
        request(){
            const params = {}
            const headers = {}

            this.data.data.forEach(item=>{
                params[item.param] = item.value
            })
            this.data.header.forEach(item=>{
                headers[item.key] = item.value
            })
            const config = {
                url:this.data.domain + this.data.url,
                method:this.data.method,
                headers:headers
            }
            if(this.data.method == 'GET'){
                config.params = params
            }else{
                config.data = params
            }
            this.loading = true
            this.response = true
            this.$nextTick(()=>{
                var iframeElement = this.$refs.iframe
                var iframeDoc = iframeElement.contentDocument || iframeElement.contentWindow.document;
                axios(config).then(res=>{
                    var html = JSON.stringify(res.data, null, "\t")
                    html =  hljs.highlight(html, {language: 'json'}).value
                    html = '<pre><code class="hljs" style="background: none;">'+html+"</code></pre>"
                    iframeDoc.open();
                    iframeDoc.write("<link rel='stylesheet' type='text/css' href='/apidoc/css/atom-one-light'/>");
                    iframeDoc.write(html);
                    iframeDoc.close();
                    setTimeout(()=>{
                        if(iframeDoc.body.scrollHeight >= 500){
                            iframeElement.height = 500
                        }else{
                            iframeElement.height = iframeDoc.body.scrollHeight
                        }
                    })
                }).catch((error)=>{
                    var html = JSON.stringify(error.response.data, null, "\t")
                    html =  hljs.highlight(html, {language: 'json'}).value
                    html = '<pre><code class="hljs" style="background: none;">'+html+"</code></pre>"
                    iframeDoc.open();
                    iframeDoc.write("<link rel='stylesheet' type='text/css' href='/apidoc/css/atom-one-light'/>");
                    iframeDoc.write(html);
                    iframeDoc.close();
                    setTimeout(()=>{
                        if(iframeDoc.body.scrollHeight >= 500){
                            iframeElement.height = 500
                        }else{
                            iframeElement.height = iframeDoc.body.scrollHeight
                        }
                    })
                }).finally(()=>{
                    this.loading = false
                })
            })
        }
    },
    template:`<div >
                <div v-if="data.type =='group'" class="font-bold border-b px-8 py-3 text-3xl" :id="'doc'+data.id">{{data.name}}</div>
                <div v-else class="p-8 border-b-2">
                <div class="font-medium" :id="'doc'+data.id">
                <span v-if="data.method == 'GET'" class="text-green-500 mr-3 font-bold">{{data.method}}</span>
                <span v-if="data.method == 'POST'" class="text-yellow-500 mr-3 font-bold">{{data.method}}</span>
                <span v-if="data.method == 'PUT'" class="text-blue-500 mr-3 font-bold">{{data.method}}</span>
                <span v-if="data.method == 'DELETE'" class="text-red-500 mr-3 font-bold">{{data.method}}</span>
                {{data.name}}</div>
               <div class="flex items-center">
                    <div class="bg-gray-50 border rounded-md text-xs text-gray-700 px-3 py-2  my-3 flex-1"><span>{{data.domain}}</span>{{data.url}}</div>
                    <button type="button" class="ml-3 px-6 h-8 rounded-md border shadow text-gray-600 hover:bg-gray-50" @click="request">运行</button>
                </div>
                <!-- 请求头-->
                <div class="mb-5" v-if="data.header.length > 0">
                    <div class="border-b font-bold mb-3">请求头(HEADERS)</div>
                    <table class="table-fixed w-full">
                        <thead>
                        <tr class="bg-gray-100 font-medium leading-8">
                            <td class="border w-1/4"><span class="ml-3">参数</span></td>
                            <td class="border w-1/4"><span class="ml-3">值</span></td>
                            <td class="border w-2/4"><span class="ml-3">描述</span></td>
                        </tr>
                        </thead>
                        <tbody class="text-sm">
                        <tr class="leading-10" v-for="item in data.header">
                            <td class="border text-gray-800 font-medium"><input type="text" class="ml-3 w-10/12 my-2 h-8 focus:outline-none focus:ring focus:border-blue-200" v-model="item.key"/></td>
                            <td class="border text-gray-800"><input type="text" class="ml-3 w-10/12 my-2 h-8 focus:outline-none focus:ring focus:border-blue-200" v-model="item.value"/></td>
                            <td class="border text-gray-500 whitespace-pre"><div class="ml-3" v-html="item.desc"></div></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 请求参数-->
                <div class="mb-5" v-if="data.data.length > 0">
                    <div class="border-b font-bold mb-3">请求参数(PARAMS)</div>
                    <table class="table-fixed w-full">
                        <thead>
                        <tr class="bg-gray-100 font-medium leading-8">
                            <td class="border w-1/4"><span class="ml-3">参数</span></td>
                            <td class="border w-1/4"><span class="ml-3">值</span></td>
                            <td class="border w-2/4"><span class="ml-3">描述</span></td>
                        </tr>
                        </thead>
                        <tbody class="text-sm">
                        <tr class="leading-10" v-for="item in data.data">
                            <td class="border text-gray-800 font-medium"><input type="text" class="ml-3 w-10/12 my-2 h-8 focus:outline-none focus:ring focus:border-blue-200" v-model="item.param"/></td>
                            <td class="border text-gray-800"><input type="text" class="ml-3 w-10/12 my-2 h-8 focus:outline-none focus:ring focus:border-blue-200" v-model="item.value"/></td>
                            <td class="border text-gray-500 whitespace-pre"><div class="ml-3" v-html="item.desc"></div></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <!-- 响应-->
                <div v-if="response !== false">
                    <div class="border-b font-bold my-3 py-3 flex items-center justify-between cursor-pointer" @click="collapse = !collapse">
                    <div>响应(RESPONSE)</div>
                    <div :class="['h-3','w-3',collapse?'transform rotate-90':'']">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M340.864 149.312a30.592 30.592 0 0 0 0 42.752L652.736 512 340.864 831.872a30.592 30.592 0 0 0 0 42.752 29.12 29.12 0 0 0 41.728 0L714.24 534.336a32 32 0 0 0 0-44.672L382.592 149.376a29.12 29.12 0 0 0-41.728 0z"></path></svg>
                     </div>
</div>
                    <div class="border rounded-md text-sm text-gray-200 px-3 py-2 my-3" v-show="collapse">
                    <div><svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg> <iframe ref="iframe" width="100%" v-show="!loading" height="auto"></iframe></div>
                    </div>
                </div>
                <div v-for="(item,index) in data.response" :key="index">
                    <div class="border-b font-bold my-3 py-3 flex items-center justify-between cursor-pointer" @click="item.collapse = !item.collapse">
                        <div>返回示例（Example）<span v-if="item.title">- {{item.title}}</span></div>
                        <div :class="['h-3','w-3',item.collapse?'transform rotate-90':'']">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M340.864 149.312a30.592 30.592 0 0 0 0 42.752L652.736 512 340.864 831.872a30.592 30.592 0 0 0 0 42.752 29.12 29.12 0 0 0 41.728 0L714.24 534.336a32 32 0 0 0 0-44.672L382.592 149.376a29.12 29.12 0 0 0-41.728 0z"></path></svg>
                        </div>
                    </div>
                    <div class="bg-gray-900 border rounded-md text-sm text-gray-200 px-3 py-2 my-3" v-show="item.collapse">
                    <iframe ref="iframes" width="100%" height="100%" :data-response="item.data"></iframe>
                    </div>
                </div>
        
</div>
        <template  v-for="item in data.children">
               <doc-item :data="item"></doc-item>
        </template >
</div>`
})

