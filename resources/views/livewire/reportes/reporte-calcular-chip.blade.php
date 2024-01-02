<div>
    <div class="sm:px-6 w-full pt-12 pb-4">

        <div class="bg-gray-200  px-8 py-4 rounded-xl w-full ">

            <div class=" items-center md:block sm:block">
                <div class="p-2 w-64 my-4 md:w-full">
                    <h2 class="text-indigo-600 font-bold text-3xl">
                        <i class="fa-solid fa-square-poll-vertical fa-xl"></i>
                        &nbsp;REPORTE GENERAL DE CHIPS MOTORGAS-COMPANY
                    </h2>
                </div>

                <div class="w-full  items-center md:flex md:flex-row md:justify-between ">
                    
                    <div class="flex bg-white items-center p-2 w-1/2 rounded-md mb-4 space-x-4">
                        <span>Taller: </span>
                        <select wire:model="ta"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($talleres)
                                @foreach ($talleres as $taller)
                                    <option class="" value="{{ $taller->id }}">{{ $taller->nombre }}</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="flex bg-white items-center p-2 w-1/2 rounded-md mb-4 space-x-4">
                        <span>Inspector: </span>
                        <select wire:model="ins"
                            class="bg-gray-50 mx-2 border-indigo-500 rounded-md outline-none ml-1 block w-full truncate">
                            <option value="">SELECCIONE</option>
                            @isset($inspectores)
                                @foreach ($inspectores as $inspector)
                                    <option value="{{ $inspector->id }}">{{ $inspector->name }}</option>
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

                    <button wire:click="obtenerChipsConsumidos"
                        class="bg-green-400 px-6 py-4 w-full md:w-auto rounded-md text-white font-semibold tracking-wide cursor-pointer mb-4">
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

        @if ($chipsConsumidos->isNotEmpty())
        <div class="mt-4">
            @foreach ($chipsConsumidos->groupBy('nombreInspector') as $inspector => $chips)
                <div class="mb-4">
                    <h3 class="text-indigo-600 text-xl font-bold mb-4">{{$inspector }}</h3>

                    <div class="overflow-x-auto m-auto w-full" wire:ignore>
                        <div class="inline-block min-w-full py-2 sm:px-6">
                            <div class="overflow-hidden">
                                <table class="min-w-full border text-center text-sm font-light dark:border-neutral-500">
                                    <thead class="border-b font-medium dark:border-neutral-500">
                                        <tr class="bg-indigo-200">
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">ID</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Servicio</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Estado</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Ubicación</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Grupo</th>
                                            <th scope="col" class="border-r px-6 py-4 dark:border-neutral-500">Fecha de Actualización</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($chips as $chip)
                                            <tr class="border-b dark:border-neutral-500 bg-orange-200">
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $chip->id }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">
                                                    <div >
                                                        @if (Str::startsWith($chip->ubicacion, 'En poder del cliente '))
                                                            <p class="text-sm leading-none text-gray-600 ml-2 p-2 bg-blue-200 rounded-full">
                                                                Chip por deterioro
                                                            </p> 
                                                        @else
                                                            <p class="text-sm leading-none text-gray-600 ml-2 p-2 bg-green-200 rounded-full">
                                                                Conversión a GNV + chip
                                                            </p>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">@if ($chip->estado == 4)
                                                    Consumido
                                                @else
                                                    {{ $chip->estado }}
                                                @endif</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $chip->ubicacion }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $chip->grupo }}</td>
                                                <td class="whitespace-nowrap border-r px-6 py-3 dark:border-neutral-500">{{ $chip->updated_at }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    </div>
</div>
