<div>
    <div class="sm:px-6 w-full pt-12 pb-4">
        <div class="bg-gray-200  px-8 py-4 rounded-xl w-full ">

            <div class=" items-center md:block sm:block">
                <div class="p-2 w-64 my-4 md:w-full">
                    <h2 class="text-indigo-600 font-bold text-3xl">
                        <i class="fa-solid fa-square-poll-vertical fa-xl"></i>
                        &nbsp;REPORTE GENERAL DE LESLIE GNV
                    </h2>
                </div>

                <div class="w-full  items-center md:flex md:flex-row md:justify-between ">
                    <div class="flex bg-gray-50 items-center p-2 rounded-md mb-4">
                        <span>Taller: </span>
                        <select wire:model="taller"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($talleres)
                                @foreach ($talleres as $taller)
                                    <option value="{{ $taller }}">{{ $taller }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="flex bg-white items-center p-2 rounded-md mb-4">
                        <span>Inspector: </span>
                        <select wire:model="ins"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($inspectores)
                                @foreach ($inspectores as $inspector)
                                    <option value="{{ $inspector }}">{{ $inspector }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="flex bg-white items-center p-2 w-1/2 md:w-48 rounded-md mb-4 ">
                            <span>Desde: </span>
                            <x-date-picker wire:model="fechaInicio" placeholder="Fecha de inicio"
                                class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate" />
                        </div>
                        <div class="flex bg-white items-center p-2 w-1/2 md:w-48 rounded-md mb-4 ">
                            <span>Hasta: </span>
                            <x-date-picker wire:model="fechaFin" placeholder="Fecha de Fin"
                                class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate" />
                        </div>
                    </div>

                    <button wire:click="calcularReporte"
                        class="bg-indigo-600 px-6 py-4 w-full md:w-auto rounded-md text-white font-semibold tracking-wide cursor-pointer mb-4">
                        <p class="truncate"> Generar reporte </p>
                    </button>
                </div>
                <div class="w-auto my-4">
                    <x-jet-input-error for="taller" />
                    <x-jet-input-error for="ins" />
                    <x-jet-input-error for="fechaInicio" />
                    <x-jet-input-error for="fechaFin" />
                </div>
                <div class="w-full text-center font-semibold text-gray-100 p-4 mb-4 border rounded-md bg-indigo-400 shadow-lg"
                    wire:loading>
                    CARGANDO <i class="fa-solid fa-spinner animate-spin"></i>
                </div>
            </div>
        </div>

        @if (isset($resultados))
            @if (count($resultados))
                <div class="flex flex-col my-4 py-4 rounded-md bg-white px-4 justify-center">
                    {{--
                        <div class="m-auto flex justify-center items-center bg-gray-300 rounded-md w-full p-4">
                            <button wire:click="exportarExcel"
                                class="bg-green-400 px-6 py-4 w-1/3 text-sm rounded-md text-sm text-white font-semibold tracking-wide cursor-pointer ">
                                <p class="truncate"><i class="fa-solid fa-file-excel fa-lg"></i> Desc. Excel </p>
                            </button>
                        </div> --}}
                    <div class="overflow-x-auto m-auto w-full" wire:ignore>
                        <div class="inline-block min-w-full py-2 sm:px-6">
                            <div class="overflow-hidden">
                                <table class="min-w-full border text-center text-sm font-light dark:border-neutral-500">
                                    <thead class="border-b font-medium dark:border-neutral-500">
                                        <tr class="bg-indigo-200">
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">#</th>
                                            {{--<th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Fecha</th>--}}
                                            {{--<th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Placa</th>--}}
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Taller</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Certificador</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Anuales</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Conversión</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Desmontes</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($resultados as $key => $item)
                                            <tr class="border-b dark:border-neutral-500 bg-orange-200">
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $key + 1 }}</td>
                                                {{--<td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                    @if (isset($item['detalles'][0]['fecha']))
                                                        {{ $item['detalles'][0]['fecha'] }}
                                                    @endif
                                                </td>--}}  
                                                {{--<td class="whitespace-nowrap border-r px-6 py-3 font-medium dark:border-neutral-500">{{ $item['detalles'][0]['placa'] ?? '' }}</td>--}}
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $item['detalles'][0]['taller'] ?? '' }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $item['certificador'] }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $item['totalAnuales'] }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $item['totalConversiones'] }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $item['totalDesmontes'] }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500"></td>

                                                <!-- Agrega más celdas según tus necesidades -->
                                            </tr>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="w-full text-center font-semibold text-gray-100 p-4 mb-4 border rounded-md bg-indigo-400 shadow-lg"
                    wire:loading.class="hidden">
                    No se encontraron resultados.
                </div>
            @endif
        @endif
    </div>
</div>
